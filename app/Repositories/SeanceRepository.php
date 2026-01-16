<?php

    namespace App\Repositories;

    use App\Models\Database;
    use PDO;
    use Exception;

    class SeanceRepository {
        private PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function getPendingDemandsCount(int $coachUserId): int {
            $stmt = $this->db->prepare("SELECT COUNT(*) 
                                        FROM seance 
                                        WHERE id_coach = (SELECT id_coach FROM coach WHERE id_user = :id) 
                                        AND statut = 'en attente'");
            $stmt->execute(['id' => $coachUserId]);
            return (int) $stmt->fetchColumn();
        }

        public function getSessionCountForToday(int $coachUserId): int {    
            $stmt = $this->db->prepare("SELECT COUNT(*)
                                        FROM seance
                                        WHERE id_coach = (SELECT id_coach FROM coach WHERE id_user = :id) 
                                        AND date_seance = CURRENT_DATE 
                                        AND statut = 'accepte'");
            $stmt->execute(['id' => $coachUserId]);
            return (int) $stmt->fetchColumn();
        }

        public function getDetailPendingDemands(int $coachUserId): array {
            $sql = "SELECT s.id_seance, s.date_seance, s.heure, s.statut, 
                        u_sportif.nom as client_nom, u_sportif.prenom as client_prenom, 
                        d.nom_discipline
                    FROM seance s
                    JOIN sportif sp ON s.id_sportif = sp.id_sportif
                    JOIN utilisateur u_sportif ON sp.id_user = u_sportif.id_user
                    LEFT JOIN disciplines d ON s.id_discipline = d.id_discipline
                    JOIN coach c ON s.id_coach = c.id_coach
                    WHERE c.id_user = :id 
                    AND s.statut = 'en attente'
                    ORDER BY s.date_seance ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $coachUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAllAvailableSlots(): array {
            $sql = "SELECT d.*, u.nom as coach_nom, u.prenom as coach_prenom, disc.nom_discipline
                    FROM disponibilite d
                    JOIN coach c ON d.id_coach = c.id_coach
                    JOIN utilisateur u ON c.id_user = u.id_user
                    LEFT JOIN coach_discipline cd ON c.id_coach = cd.id_coach
                    LEFT JOIN disciplines disc ON cd.id_discipline = disc.id_discipline
                    WHERE d.statut = 'libre'
                    ORDER BY d.date_disponibilite ASC, d.heure_debut ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         * SPORTIF METHODS
         */

        public function getSportifSessionsCount(int $sportifUserId, string $statut): int {
            $sql = "SELECT COUNT(*)
                    FROM seance s
                    JOIN sportif sp ON s.id_sportif = sp.id_sportif
                    WHERE sp.id_user = :id AND s.statut = :statut";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $sportifUserId, 'statut' => $statut]);
            return (int) $stmt->fetchColumn();
        }

        public function getSportifSessionsCompletesCount(int $sportifUserId): int {
            // Changed CURDATE() to CURRENT_DATE for PostgreSQL
            $sql = "SELECT COUNT(s.id_seance)
                    FROM seance s
                    JOIN sportif sp ON s.id_sportif = sp.id_sportif
                    WHERE sp.id_user = :id AND s.date_seance < CURRENT_DATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $sportifUserId]);
            return (int) $stmt->fetchColumn();
        }

        public function getSportifReservations(int $sportifUserId): array {
            $sql = "SELECT s.*, u.nom AS coach_nom, u.prenom AS coach_prenom 
                    FROM seance s
                    JOIN coach c ON s.id_coach = c.id_coach
                    JOIN utilisateur u ON c.id_user = u.id_user
                    JOIN sportif sp ON s.id_sportif = sp.id_sportif
                    WHERE sp.id_user = :id
                    ORDER BY s.date_seance ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $sportifUserId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         * BOOKING & UPDATES
         */

        public function rejectAndReleaseSlot(int $id_seance): bool {
            try {
                $this->db->beginTransaction();

                // 1. Get the linked availability ID
                $stmtGet = $this->db->prepare("SELECT id_disponibilite FROM seance WHERE id_seance = :id");
                $stmtGet->execute(['id' => $id_seance]);
                $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    // 2. Make the slot free again
                    $stmtDisp = $this->db->prepare("UPDATE disponibilite SET statut = 'libre' WHERE id_disponibilite = :id_d");
                    $stmtDisp->execute(['id_d' => $row['id_disponibilite']]);
                }

                // 3. Update session status
                $stmtS = $this->db->prepare("UPDATE seance SET statut = 'annule' WHERE id_seance = :id");
                $stmtS->execute(['id' => $id_seance]);

                $this->db->commit();
                return true;
            } catch (Exception $e) {
                $this->db->rollBack();
                return false;
            }
        }

        public function updateStatus(int $id_seance, string $status): bool {
            $sql = "UPDATE seance SET statut = :status WHERE id_seance = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['status' => $status, 'id' => $id_seance]);
        }

        public function getAvailabilityDetail(int $disp_id): ?array {
            $sql = "SELECT d.*, 
                        c.id_coach, 
                        u.nom AS coach_nom, 
                        u.prenom AS coach_prenom, 
                        cd.id_discipline
                    FROM disponibilite d 
                    JOIN coach c ON d.id_coach = c.id_coach 
                    JOIN utilisateur u ON c.id_user = u.id_user 
                    LEFT JOIN coach_discipline cd ON c.id_coach = cd.id_coach
                    WHERE d.id_disponibilite = :id 
                    AND d.statut = 'libre' 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $disp_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        }

        public function createBooking(int $id_sportif, array $dispo): bool {
            try {
                // Start transaction: if one query fails, both are cancelled
                $this->db->beginTransaction();

                // 1. Insert into seance table
                $sqlSeance = "INSERT INTO seance (date_seance, heure, statut, id_sportif, id_coach, id_discipline, id_disponibilite) 
                            VALUES (:date, :heure, 'en attente', :id_s, :id_c, :id_disc, :id_d)";
                
                $stmtS = $this->db->prepare($sqlSeance);
                $stmtS->execute([
                    'date'    => $dispo['date_disponibilite'],
                    'heure'   => $dispo['heure_debut'],
                    'id_s'    => $id_sportif, // This is the logged-in user's ID
                    'id_c'    => $dispo['id_coach'],
                    'id_disc' => $dispo['id_discipline'] ?? 1, // Fallback to a default discipline if none linked
                    'id_d'    => $dispo['id_disponibilite']
                ]);

                // 2. Update the slot status so no one else can book it
                $sqlUpd = "UPDATE disponibilite SET statut = 'reserve' WHERE id_disponibilite = :id";
                $stmtU = $this->db->prepare($sqlUpd);
                $stmtU->execute(['id' => $dispo['id_disponibilite']]);

                // Save changes
                $this->db->commit();
                return true;

            } catch (Exception $e) {
                // Something went wrong, undo everything
                $this->db->rollBack();
                error_log("Erreur réservation : " . $e->getMessage());
                return false;
            }
        }

        public function refuseAndReleaseSlot(int $id_seance): bool {
            try {
                $this->db->beginTransaction();

                // 1. Find the availability ID linked to this session
                $stmtGet = $this->db->prepare("SELECT id_disponibilite FROM seance WHERE id_seance = :id");
                $stmtGet->execute(['id' => $id_seance]);
                $seance = $stmtGet->fetch(PDO::FETCH_ASSOC);

                if ($seance && !empty($seance['id_disponibilite'])) {
                    // 2. Set the availability back to 'libre'
                    $stmtDisp = $this->db->prepare("UPDATE disponibilite SET statut = 'libre' WHERE id_disponibilite = :id_d");
                    $stmtDisp->execute(['id_d' => $seance['id_disponibilite']]);
                }

                // 3. Update the session status to 'refuse'
                $stmtS = $this->db->prepare("UPDATE seance SET statut = 'refuse' WHERE id_seance = :id");
                $stmtS->execute(['id' => $id_seance]);

                $this->db->commit();
                return true;
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                error_log("Error refusing session: " . $e->getMessage());
                return false;
            }
        }
    }
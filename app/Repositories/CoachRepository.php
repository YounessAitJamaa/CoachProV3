<?php

    namespace App\Repositories;

    use App\Models\Database;
    use App\Models\Coach;

    use PDO;
    use PDOException;

    class CoachRepository {
        private PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function register(Coach $coach) {
            try {
                $this->db->beginTransaction();

                $sql1 = "INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, date_inscription, id_role, photo, telephone)
                        VALUES (:nom, :prenom, :email, :mot_de_passe, :date_inscription, :role, :photo, :telephone) 
                        RETURNING id_user";
                
                $stmt1 = $this->db->prepare($sql1);
                $stmt1->execute([
                    'nom'              => $coach->getNom(),
                    'prenom'           => $coach->getPrenom(),
                    'email'            => $coach->getEmail(),
                    'mot_de_passe'     => password_hash($coach->getMotDePasse(), PASSWORD_DEFAULT),
                    'date_inscription' => $coach->getDateInscription(),
                    'role'             => 2,
                    'photo'            => $coach->getPhoto(),
                    'telephone'        => $coach->getTelephone()
                ]);

                $userId = $stmt1->fetchColumn();
                
                $sql2 = "INSERT INTO coach (biographie, experience, niveau, adresse, id_user)
                        VALUES (:biographie, :experience, :niveau, :adresse, :id_u)";
                
                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([
                    'biographie' => $coach->getBiographie(),
                    'experience' => $coach->getExperience(),
                    'niveau' => $coach->getNiveau(),
                    'adresse' => $coach->getAdresse(),
                    'id_u' => $userId
                ]);

                $this->db->commit();
                return true;

            } catch (PDOException $err) {
                $this->db->rollBack();
                echo "<h1>Database Error</h1>";
                echo "<p>Message: " . $err->getMessage() . "</p>";
                echo "<p>Code: " . $err->getCode() . "</p>";
                die();
                return false;
            }
        }

        public function getAllDisciplines(): array {
            return $this->db->query("SELECT * FROM disciplines ORDER BY nom_discipline ASC")->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getAvailableCoaches(?int $disciplineId = null): array {
            $sql = "SELECT c.*, u.nom, u.prenom, u.photo, u.telephone, 
                        (SELECT AVG(note) FROM avis WHERE id_coach = c.id_coach) as moyenne_note,
                        (SELECT COUNT(*) FROM avis WHERE id_coach = c.id_coach) as total_avis
                    FROM coach c
                    JOIN utilisateur u ON c.id_user = u.id_user";
            
            if ($disciplineId) {
                $sql .= " JOIN coach_discipline cd ON c.id_coach = cd.id_coach 
                        WHERE cd.id_discipline = :disc_id";
            }

            $stmt = $this->db->prepare($sql);
            if ($disciplineId) {
                $stmt->bindValue(':disc_id', $disciplineId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getCoachById(int $id): ?array {
            $sql = "SELECT c.*, u.nom as coach_nom, u.prenom as coach_prenom, u.email, u.photo, u.telephone,
                    (SELECT AVG(note) FROM avis WHERE id_coach = c.id_coach) as moyenne_note,
                    (SELECT COUNT(*) FROM avis WHERE id_coach = c.id_coach) as total_avis
                    FROM coach c
                    JOIN utilisateur u ON c.id_user = u.id_user
                    WHERE c.id_coach = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function getDisciplinesByCoachId(int $id): array {
            $sql = "SELECT d.nom_discipline FROM disciplines d
                    JOIN coach_discipline cd ON d.id_discipline = cd.id_discipline
                    WHERE cd.id_coach = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getCoachDisponibilites(int $id): array {
            $sql = "SELECT * FROM disponibilite 
                    WHERE id_coach = :id AND statut = 'libre' 
                    AND date_disponibilite >= CURRENT_DATE
                    ORDER BY date_disponibilite, heure_debut ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function updateFullCoachProfile(int $userId, int $coachId, array $data): bool {
            try {
                $this->db->beginTransaction();

                // 1. Update Identity
                $sql1 = "UPDATE utilisateur SET nom = :n, prenom = :p, email = :e, telephone = :t, photo = :ph WHERE id_user = :id";
                $this->db->prepare($sql1)->execute([
                    'n' => $data['nom'], 'p' => $data['prenom'], 'e' => $data['email'], 
                    't' => $data['telephone'], 'ph' => $data['photo'], 'id' => $userId
                ]);

                // 2. Update Pro Info
                $sql2 = "UPDATE coach SET biographie = :b, experience = :ex, niveau = :ni, adresse = :ad WHERE id_coach = :id";
                $this->db->prepare($sql2)->execute([
                    'b' => $data['biographie'], 'ex' => $data['experience'], 
                    'ni' => $data['niveau'], 'ad' => $data['adresse'], 'id' => $coachId
                ]);

                // 3. Sync Disciplines (Delete and Re-insert)
                $this->db->prepare("DELETE FROM coach_discipline WHERE id_coach = ?")->execute([$coachId]);
                $ins = $this->db->prepare("INSERT INTO coach_discipline (id_coach, id_discipline) VALUES (?, ?)");
                foreach ($data['disciplines'] as $discId) {
                    $ins->execute([$coachId, $discId]);
                }

                $this->db->commit();
                return true;
            } catch (PDOException $e) {
                $this->db->rollBack();
                return false;
            }
        }

        public function getCoachDisciplinesIds(int $coachId): array 
        {
            $sql = "SELECT id_discipline FROM coach_discipline WHERE id_coach = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $coachId]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        public function addAvailability(int $coachId, string $date, string $start, string $end): bool 
        {
            try {
                $sql = "INSERT INTO disponibilite (id_coach, date_disponibilite, heure_debut, heure_fin, statut) 
                        VALUES (:id, :d, :s, :e, 'libre')";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    'id' => $coachId,
                    'd'  => $date,
                    's'  => $start,
                    'e'  => $end
                ]);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                return false;
            }
        }
                
        
    }
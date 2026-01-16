<?php 

    namespace App\Repositories;

    use App\Models\Database;
    use App\Models\Coach;
    use PDO;
    use PDOException;

    class UserRepository
    {
        private PDO $db;
        
        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function findByEmail(string $email) {
            try {
                $sql = "SELECT u.*, r.nom_role
                        FROM utilisateur u
                        JOIN role r ON u.id_role = r.id_role
                        WHERE u.email = :email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email]);

                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                return null;
            }
        }

        public function getSportifProfileData(int $userId): array {
            $sql = "SELECT u.*, s.date_naissance 
                    FROM utilisateur u 
                    JOIN sportif s ON u.id_user = s.id_user 
                    WHERE u.id_user = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ?: [];
        }

        public function updateSportifProfile(int $userId, array $data): bool {
            try {
                $this->db->beginTransaction();

                $sql1 = "UPDATE utilisateur 
                        SET nom = :nom, 
                            prenom = :prenom, 
                            email = :email,
                            telephone = :telephone, 
                            photo = :photo 
                        WHERE id_user = :id";
                $stmt1 = $this->db->prepare($sql1);
                $stmt1->execute([
                    'nom'       => $data['nom'],
                    'prenom'    => $data['prenom'],
                    'email'     => $data['email'],
                    'telephone' => $data['telephone'],
                    'photo'     => $data['photo'],
                    'id'        => $userId
                ]);

                if (isset($data['date_naissance'])) {
                    $sql2 = "UPDATE sportif 
                            SET date_naissance = :dob 
                            WHERE id_user = :id";
                    $stmt2 = $this->db->prepare($sql2);
                    $stmt2->execute([
                        'dob' => $data['date_naissance'],
                        'id'  => $userId
                    ]);
                }

                $this->db->commit();
                return true;
            } catch (\Exception $e) {
                $this->db->rollBack();
                error_log("Update Profile Error: " . $e->getMessage());
                return false;
            }
        }

        public function getUserById(int $id): ?array {
            $sql = "SELECT u.*, r.nom_role 
                    FROM utilisateur u 
                    JOIN role r ON u.id_role = r.id_role 
                    WHERE u.id_user = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        public function getCoachDetails(array $userData): Coach {
            $sql = "SELECT * FROM coach WHERE id_user = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userData['id_user']]);
            $coachData = $stmt->fetch(PDO::FETCH_ASSOC);

            return new Coach(
                $userData['id_user'],
                $userData['nom'],
                $userData['prenom'],
                $userData['email'],
                $userData['photo'],
                $userData['telephone'],
                $userData['mot_de_passe'],
                $userData['date_inscription'],
                $coachData['id_coach'],
                $coachData['biographie'] ?? '',
                $coachData['experience'] ?? 0,
                $coachData['niveau'] ?? '',
                $coachData['adresse'] ?? ''
            );
        }
    }
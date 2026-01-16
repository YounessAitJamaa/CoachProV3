<?php

    namespace App\Repositories;

    use App\Models\Database;
    use App\Models\Sportif;

    use PDO;
    use PDOException;

    class SportifRepository {
        private PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function register(Sportif $sportif) {
            try {
                $this->db->beginTransaction();

                $sql1 = "INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, date_inscription, id_role, photo, telephone)
                        VALUES (:nom, :prenom, :email, :mot_de_passe, :date_inscription, :role, :photo, :telephone)
                        RETURNING id_user";
                $stmt1 = $this->db->prepare($sql1);
                $stmt1->execute([
                    'nom'               => $sportif->getNom(),
                    'prenom'            => $sportif->getPrenom(),
                    'email'             => $sportif->getEmail(),
                    'mot_de_passe'      => password_hash($sportif->getMotDePasse(), PASSWORD_DEFAULT),
                    'date_inscription'  => $sportif->getDateInscription(),
                    'role'              => 1,
                    'photo'             => $sportif->getPhoto(),
                    'telephone'         => $sportif->getTelephone()
                ]);

                $userId = $stmt1->fetchColumn();
                
                $sql2 = "INSERT INTO sportif (date_naissance, id_user)
                        VALUES (:date_naissance, :id_u)";
                $stmt2 = $this->db->prepare($sql2);
                $stmt2->execute([
                    'date_naissance' => $sportif->getDatNaissance(),
                    'id_u' => $userId
                ]);

                $this->db->commit();
                return true;

            }catch (PDOException $err) {
                $this->db->rollBack();
                return false;
            }
        }

        public function getSportifByUserId(int $userId): ?array 
        {
            $sql = "SELECT s.*, u.nom, u.prenom, u.email, u.photo, u.telephone 
                    FROM sportif s 
                    JOIN utilisateur u ON s.id_user = u.id_user 
                    WHERE s.id_user = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
                
    }
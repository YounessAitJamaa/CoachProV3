<?php 

    namespace App\Models;

    abstract class User
    {
        protected int $id_user;
        protected string $nom;
        protected string $prenom;
        protected string $email;
        protected ?string $photo;
        protected ?string $telephone;
        protected string $mot_de_passe;
        protected int $id_role;
        protected string $date_inscription;


        public function __construct(
            int $id_user,
            string $nom, 
            string $prenom, 
            string $email, 
            ?string $photo,
            ?string $telephone,
            string $mot_de_passe, 
            int $id_role, 
            string $date_inscription
        ) {
            $this->id_user = $id_user;
            $this->nom = $nom;
            $this->prenom = $prenom;
            $this->email = $email;
            $this->photo = $photo;
            $this->telephone = $telephone;
            $this->mot_de_passe = $mot_de_passe;
            $this->id_role = $id_role;
            $this->date_inscription = $date_inscription;
        }


        // Getters
        public function getId(): int { return $this->id_user; } 
        public function getNom(): string { return $this->nom; }
        public function getPrenom(): string { return $this->prenom; }
        public function getEmail(): string { return $this->email; }
        public function getPhoto(): ?string { return $this->photo; }
        public function getTelephone(): ?string { return $this->telephone; }
        public function getMotDePasse(): string { return $this->mot_de_passe; }
        public function getIdRole(): int { return $this->id_role; }
        public function getDateInscription(): string { return $this->date_inscription; }

        // Setters
        public function setNom(string $nom) { $this->nom = $nom; }
        public function setPrenom(string $prenom) { $this->prenom = $prenom; }
        public function setEmail(string $email) { $this->email = $email; }
        public function setPhoto(string $photo) { $this->photo = $photo; }
        public function setTelephone(string $telephone) { $this->telephone = $telephone; }
    }
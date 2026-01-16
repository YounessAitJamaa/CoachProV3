<?php

    namespace App\Models;

    class Coach extends User
    {
        private int $id_coach;
        private string $biographie;
        private int $experience;
        private string $niveau;
        private ?string $adresse;

        public function __construct(
            int $id_user, 
            string $nom, 
            string $prenom, 
            string $email, 
            ?string $photo, 
            ?string $telephone,
            string $mot_de_passe, 
            string $date_inscription, 
            int $id_coach,
            string $biographie, 
            int $experience,
            string $niveau, 
            ?string $adresse
        ) {
            parent::__construct($id_user, $nom, $prenom, $email, $photo, $telephone, $mot_de_passe, 2, $date_inscription);
            
            $this->id_coach = $id_coach;
            $this->biographie = $biographie;
            $this->experience = $experience;
            $this->niveau = $niveau;
            $this->adresse = $adresse;
        }


        public function getIdCoach(): int { return $this->id_coach; }
        public function getBiographie(): string { return $this->biographie; }
        public function getExperience(): int { return $this->experience; }
        public function getNiveau(): string { return $this->niveau; }
        public function getAdresse(): string { return $this->adresse; }

        public function setBiographie(string $biographie) { $this->biographie = $biographie; }
        public function setExperience(int $experience) { $this->experience = $experience; }
        public function setNiveau(string $niveau) { $this->niveau = $niveau; }

    }




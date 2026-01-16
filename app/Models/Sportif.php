<?php

    namespace App\Models;

    class Sportif extends User 
    {
        private int $id_sportif;
        private string $date_naissance;

        public function __construct( 
            string $nom, 
            string $prenom, 
            string $email, 
            string $photo,
            string $telephone,
            string $mot_de_passe,
            string $date_naissance,  
            int $id_user = 0, 
            int $id_sportif = 0, 
            string $date_inscription = ''
        ){
            if(empty($date_inscription)) $date_inscription = date('Y-m-d');
            
            parent::__construct($id_user, $nom, $prenom, $email, $photo, $telephone,$mot_de_passe, 1, $date_inscription);

            $this->id_sportif = $id_sportif;
            $this->date_naissance = $date_naissance;
        }

        
        public function getIdSportif(): int { return $this->id_sportif; }
        public function getDatNaissance(): string { return $this->date_naissance; }

    }
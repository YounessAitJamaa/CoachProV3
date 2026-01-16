<?php

namespace App\Controllers;

use App\Repositories\CoachRepository;
use App\Repositories\SeanceRepository;
use App\Repositories\UserRepository;
use Core\Controller;

class CoachController extends Controller
{

    public function index() {
        $this->checkAccess('coach');
        
        $seanceRepo = new SeanceRepository();
        $userId = $_SESSION['user_id'];

        $data = [
            'pending_count' => $seanceRepo->getPendingDemandsCount($userId),
            'today_count'   => $seanceRepo->getSessionCountForToday($userId),
            'demands'       => $seanceRepo->getDetailPendingDemands($userId)
        ];

        $this->render('coach/dashboard', $data);
    }

    public function showProfile() 
    {
        $this->checkAccess('coach');

        $userRepo = new UserRepository();
        $coachRepo = new CoachRepository();
        $userId = $_SESSION['user_id'];

        $userData = $userRepo->getUserById($userId);
        $coach = $userRepo->getCoachDetails($userData);
        
        $disciplines = $coachRepo->getDisciplinesByCoachId($coach->getIdCoach());

        $data = [
            'nom'         => $coach->getNom(),
            'prenom'      => $coach->getPrenom(),
            'biographie'  => $coach->getBiographie(),
            'photo'       => $coach->getPhoto(),
            'experience'  => $coach->getExperience(),
            'niveau'      => $coach->getNiveau(),
            'disciplines' => $disciplines
        ];

        $this->render('coach/profile', $data);
    }

    public function edit() 
    {
        $this->checkAccess('coach'); 

        $userRepo = new UserRepository();
        $coachRepo = new CoachRepository();
        
        $userData = $userRepo->getUserById($_SESSION['user_id']);
        $coach = $userRepo->getCoachDetails($userData);

        $data = [
            'coach'           => $coach,
            'alldisciplines'  => $coachRepo->getAllDisciplines(),
            'currentDiscIds'  => $coachRepo->getCoachDisciplinesIds($coach->getIdCoach()),
            'status'          => $_GET['status'] ?? null
        ];

        $this->render('coach/edit', $data);
    }

    public function update() 
    {
        $this->checkAccess('coach');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /CoachProV3/public/coach/edit');
            exit();
        }

        $coachRepo = new CoachRepository();
        $userRepo = new UserRepository();
        
        $userData = $userRepo->getUserById($_SESSION['user_id']);
        $coach = $userRepo->getCoachDetails($userData);

        // FIX: Use null coalescing (??) to fall back to current values 
        // if the form fields are missing or empty
        $data = [
            'nom'        => !empty($_POST['nom']) ? htmlspecialchars($_POST['nom']) : $coach->getNom(),
            'prenom'     => !empty($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : $coach->getPrenom(),
            'email'      => !empty($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : $coach->getEmail(),
            'telephone'  => !empty($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : $coach->getTelephone(),
            'photo'      => !empty($_POST['photo']) ? $_POST['photo'] : $coach->getPhoto(),
            'biographie' => $_POST['biographie'] ?? $coach->getBiographie(),
            'experience' => isset($_POST['experience']) ? (int)$_POST['experience'] : $coach->getExperience(),
            'niveau'     => $_POST['niveau'] ?? $coach->getNiveau(),
            'adresse'    => $_POST['adresse'] ?? $coach->getAdresse(),
            'disciplines'=> $_POST['disciplines'] ?? []
        ];

        if ($coachRepo->updateFullCoachProfile($_SESSION['user_id'], $coach->getIdCoach(), $data)) {
            header('Location: /CoachProV3/public/coach/profile?status=success');
            exit();
        } else {
            $viewData = [
                'coach'           => $coach,
                'alldisciplines'  => $coachRepo->getAllDisciplines(),
                'currentDiscIds'  => $data['disciplines'],
                'error'           => 'Erreur lors de la mise à jour du profil.'
            ];
            $this->render('coach/edit', $viewData);
        }
    }


 

    public function showAvailabilities() 
    {
        $this->checkAccess('coach');
        $coachRepo = new CoachRepository();
        
        // Get coach ID based on the logged-in user
        $userData = (new UserRepository())->getUserById($_SESSION['user_id']);
        $coach = (new UserRepository())->getCoachDetails($userData);
        $coachId = $coach->getIdCoach();

        $data = [
            'availList' => $coachRepo->getCoachDisponibilites($coachId),
            'success'   => $_GET['success'] ?? null
        ];

        $this->render('coach/availabilities', $data);
    }

    public function addAvailability() 
    {
        $this->checkAccess('coach');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $coachRepo = new CoachRepository();
            $userData = (new UserRepository())->getUserById($_SESSION['user_id']);
            $coach = (new UserRepository())->getCoachDetails($userData);

            $date = $_POST['date'];
            $start = $_POST['heure_debut'];
            $end = $_POST['heure_fin'];

            if ($coachRepo->addAvailability($coach->getIdCoach(), $date, $start, $end)) {
                header("Location: " . URLROOT . "/coach/availabilities?success=1");
                exit();
            }
        }
    }

    public function handleReservation() {
        $this->checkAccess('coach');
        
        $id_seance = $_POST['id_seance'] ?? null;
        $action = $_POST['action'] ?? null; 

        if (!$id_seance) {
            header('Location: ' . URLROOT . '/coach/dashboard');
            exit();
        }

        $repo = new SeanceRepository();

        if ($action === 'refuse') {
            $repo->refuseAndReleaseSlot((int)$id_seance);
        } else {
            $repo->updateStatus((int)$id_seance, $action);
        }

        header('Location: ' . URLROOT . '/coach/dashboard?updated=1');
        exit();
    }
}
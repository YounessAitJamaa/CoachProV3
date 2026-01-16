<?php 

    namespace App\Controllers;

    use App\Repositories\SeanceRepository;
    use App\Repositories\SportifRepository;
    use App\Repositories\UserRepository;
    use Core\Controller;

    class SportifController extends Controller
    {
        public function index() {

            $this->checkAccess('sportif');

            $repo = new SeanceRepository();
            $sportifId = $_SESSION['user_id'];

            $data = [
                'nom' => $_SESSION['user_nom'],
                'totalProgmmes' => $repo->getSportifSessionsCount($sportifId, 'accepte'),
                'totalCompletes' => $repo->getSportifSessionsCompletesCount($sportifId, 'terminee'),
                'reservations' => $repo->getSportifReservations($sportifId)
            ];

            $this->render('sportif/dashboard', $data);
        }

        public function showReservationForm() {
            $this->checkAccess('sportif');

            $repo = new \App\Repositories\CoachRepository();
            
            // 1. Get the discipline ID from the URL (if selected)
            $selectedDiscipline = isset($_GET['discipline']) && $_GET['discipline'] !== '' ? (int)$_GET['discipline'] : null;

            // 2. Fetch data from Repository
            $disciplines = $repo->getAllDisciplines();
            $coaches = $repo->getAvailableCoaches($selectedDiscipline);

            // 3. Render the view with all necessary data
            $this->render('sportif/reserver', [
                'disciplines' => $disciplines,
                'coaches' => $coaches,
                'selectedDiscipline' => $selectedDiscipline
            ]);
        }

        public function storeReservation() {
            $this->checkAccess('sportif');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $slotId = $_POST['dispo_id'] ?? null;
                $sportifId = $_SESSION['user_id'];

                $repo = new SeanceRepository();
                
                // 1. Get detail of the slot
                $dispo = $repo->getAvailabilityDetail($slotId);

                if ($dispo && $dispo['statut'] === 'libre') {
                    // 2. Create booking (already exists in your SeanceRepository!)
                    if ($repo->createBooking($sportifId, $dispo)) {
                        header('Location: /CoachProV3/public/sportif/dashboard?booked=1');
                        exit;
                    }
                }
            }
            header('Location: /CoachProV3/public/sportif/reserver?error=1');
        }

        public function showCoaches() {
            $this->checkAccess('sportif');

            $repo = new \App\Repositories\CoachRepository();
            $selectedDiscipline = isset($_GET['discipline']) ? (int)$_GET['discipline'] : null;

            $data = [
                'coaches' => $repo->getAvailableCoaches($selectedDiscipline),
                'disciplines' => $repo->getAllDisciplines(),
                'selectedDiscipline' => $selectedDiscipline
            ];

            $this->render('sportif/coaches_list', $data);
        }

        public function showCoachDetails() {
            $this->checkAccess('sportif');

            $coachId = (int)$_GET['id'];
            $repo = new \App\Repositories\CoachRepository();
            
            $coach = $repo->getCoachById($coachId);
            
            $this->render('sportif/coach_details', [
                'coach' => $coach,
                'disciplines' => $repo->getDisciplinesByCoachId($coachId), 
                'availList' => $repo->getCoachDisponibilites($coachId)     
            ]);
        }


        public function cancelSession() {

            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
                header('Location: /CoachProV3/public/login');
                exit;
            }

            $seanceId = $_POST['seance_id'] ?? null;

            if ($seanceId) {
                $repo = new SeanceRepository();
                
                
                if ($repo->rejectAndReleaseSlot((int)$seanceId)) {
                    header('Location: /CoachProV3/public/sportif/dashboard?cancelled=1');
                    exit;
                }
            }

            // If something failed, just go back
            header('Location: /CoachProV3/public/sportif/dashboard');
            exit;
        }

        public function showProfile() {
            $this->checkAccess('sportif');
            $userId = $_SESSION['user_id'];

            $userRepo = new UserRepository();
            $seanceRepo = new SeanceRepository();

            $profile = $userRepo->getSportifProfileData($userId);
            
            // Stats calculation
            $totalReserved = $seanceRepo->getSportifSessionsCount($userId, 'accepte') + 
                            $seanceRepo->getSportifSessionsCount($userId, 'en attente');
            $totalFinished = $seanceRepo->getSportifSessionsCompletesCount($userId);

            $this->render('sportif/profile', [
                'profile' => $profile,
                'stats' => [
                    'total' => $totalReserved,
                    'finished' => $totalFinished,
                    'reviews' => 4 // This could be dynamic later
                ]
            ]);
        }

        public function editProfile() {
            $this->checkAccess('sportif');
            $userRepo = new UserRepository();
            $profile = $userRepo->getSportifProfileData($_SESSION['user_id']);

            $this->render('sportif/edit_profile', ['profile' => $profile]);
        }

        public function updateProfile() {
            $this->checkAccess('sportif');
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userRepo = new UserRepository();
                $success = $userRepo->updateSportifProfile($_SESSION['user_id'], $_POST);

                if ($success) {
                    $_SESSION['user_nom'] = $_POST['nom'];
                    header('Location: /CoachProV3/public/sportif/profile?msg=success');
                    exit();
                }
            }
        }

        public function showConfirmBooking() 
        {
            $this->checkAccess('sportif');
            
            $disp_id = $_GET['disp'] ?? null;
            if (!$disp_id) {
                header('Location: ' . URLROOT . '/sportif/reserver');
                exit();
            }

            $seanceRepo = new SeanceRepository();
            $dispo = $seanceRepo->getAvailabilityDetail($disp_id);

            if (!$dispo) {
                header('Location: ' . URLROOT . '/sportif/reserver');
                exit();
            }

            $this->render('sportif/confirm_booking', ['dispo' => $dispo, 'disp_id' => $disp_id]);
        }

        public function storeBooking() 
        {
            $this->checkAccess('sportif');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $disp_id = $_POST['disp_id'] ?? null;
                
                $seanceRepo = new SeanceRepository();
                $sportifRepo = new SportifRepository();

                $sportifData = $sportifRepo->getSportifByUserId($_SESSION['user_id']);
                
                $dispo = $seanceRepo->getAvailabilityDetail($disp_id);

                if ($dispo && $sportifData) {
                    $success = $seanceRepo->createBooking($sportifData['id_sportif'], $dispo);

                    if ($success) {
                        header('Location: ' . URLROOT . '/sportif/dashboard?success=1');
                        exit();
                    }
                }
                
                header('Location: ' . URLROOT . '/sportif/reserver?error=1');
                exit();
            }
        }

        public function handleConfirmSelection() 
        {
            $this->checkAccess('sportif');

            $disp_id = $_POST['dispo_id'] ?? null;

            if (!$disp_id) {
                header('Location: ' . URLROOT . '/sportif/reserver');
                exit();
            }

            $seanceRepo = new SeanceRepository();
            $dispo = $seanceRepo->getAvailabilityDetail($disp_id);

            if (!$dispo) {
                header('Location: ' . URLROOT . '/sportif/reserver');
                exit();
            }

            $this->render('sportif/confirm_booking', [
                'dispo'   => $dispo,
                'disp_id' => $disp_id
            ]);
        }

        


    }
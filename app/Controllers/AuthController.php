<?php
    namespace App\Controllers;

    use Core\Controller;
    use App\Models\Coach;
    use App\Models\Sportif;
    use App\Repositories\CoachRepository;
    use App\Repositories\SportifRepository;
    use App\Repositories\UserRepository;

    class AuthController extends Controller {

        public function showRegister() {
            $this->render('auth/register', ['errors' => []]);
        }

        public function showLogin() {
            $this->render('auth/login', ['errors' => []]);
        }

        public function handleLogin() {
            
            if($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';
                $password = $_POST['mot_de_passe'] ?? ''; 

                $userRepo = new UserRepository();
                $user = $userRepo->findByEmail($email);

                if($user && password_verify($password, $user['mot_de_passe'])) {

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['role'] = $user['nom_role'];

                    if($user['nom_role'] === 'coach') {
                        header('Location: /CoachProV3/public/coach/dashboard');
                    } else {
                        header('Location: /CoachProV3/public/sportif/dashboard');
                    }
                    exit;

                } else {
                    $this->render('auth/login', [
                        'errors' => ['Email ou mote de passe incorrect']
                    ]);
                }

            }
        }

        public function handleRegister() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $roleType = $_POST['role'] ?? ''; // 'coach' or 'sportif' from your HTML

                // print_r($_POST);
                
                if ($roleType === 'coach') {
                    $this->registerCoach();
                } else if ($roleType === 'sportif') {
                    $this->registerSportif();
                } else {
                    $this->render('auth/register', ['errors' => ['Veuillez choisir un rôle.']]);
                }
            }
        }



        private function registerCoach() {
            $repo = new CoachRepository();

            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $pass = $_POST['mot_de_passe'] ?? $_POST['password'] ?? '';

            if (empty($nom) || empty($email)) {
                $this->render('auth/register', ['errors' => ["Nom et Email sont obligatoires"]]);
                return;
            }

            $coach = new Coach(
                0, $nom, $prenom, $email, 
                'default.jpeg', '0000000000', $pass,
                date('Y-m-d'), 0, 'Bio...', 0, 'Débutant', ''
            );

            if ($repo->register($coach)) {
                header('Location: ' . URLROOT . '/login?success=1');
                exit;
            }
        }
        private function registerSportif() {
            $repo = new SportifRepository();
            $sportif = new Sportif(
                $_POST['nom'], 
                $_POST['prenom'], 
                $_POST['email'], 
                '../../assets/images/default.jpeg',    // photo
                '0000000000',                                 // telephone 
                $_POST['mot_de_passe'],
                '1990-01-01',                       // date_naissance
                0,                                  // id_user
                0                                   // id_sportif
            );

            if ($repo->register($sportif)) {
                header('Location: /CoachProV3/public/login?success=1');
                exit;
            } else {
                $this->render('auth/register', ['errors' => ["Erreur lors de l'inscription du sportif"]]);
            }
        }

        public function logout() {
            if(session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            session_unset();

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            session_destroy();

            header('Location: /CoachProV3/public/login');
            exit;
        }
    }
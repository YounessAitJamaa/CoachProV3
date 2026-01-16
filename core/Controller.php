<?php 

    namespace core;

    class Controller 
    {
        public function render($view, $data = []) {
            
            extract($data);

            $viewPath = __DIR__ . "/../app/Views/$view.php";

            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                die("View file not found :  . $view ");
            }
        }

        protected function checkAccess(string $role) {
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $role) {
                header('Location: /CoachProV3/public/login');
                exit;
            }
        }
    }
   

?>
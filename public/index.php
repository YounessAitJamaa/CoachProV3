<?php 

    session_start();
     
    require_once __DIR__ . '/../vendor/autoload.php';

    use App\Models\Database;
    use Core\Router;
    use App\Controllers\AuthController;
    use App\Controllers\CoachController;
    use App\Controllers\SportifController;

    define('URLROOT', '/CoachProV3/public');

    Database::getConnection();

    $router = new Router();

    $router->add('GET', '/register', [AuthController::class, 'showRegister']);
    $router->add('POST', '/register', [AuthController::class, 'handleRegister']);
    $router->add('GET', '/login', [AuthController::class, 'showLogin']);
    $router->add('POST', '/login', [AuthController::class, 'handleLogin']);
    $router->add('GET', '/logout', [AuthController::class, 'logout']);

    // Sportif Routes
    $router->add('GET', '/sportif/dashboard', [SportifController::class, 'index']);
    $router->add('POST', '/sportif/cancel-session', [SportifController::class, 'cancelSession']);
    $router->add('GET', '/sportif/reserver', [SportifController::class, 'showReservationForm']);
    $router->add('POST', '/sportif/reserver', [SportifController::class, 'storeReservation']);
    $router->add('GET',  '/sportif/reserver', [SportifController::class, 'showReservationForm']);
    $router->add('POST', '/sportif/confirm', [SportifController::class, 'handleConfirmSelection']);
    $router->add('POST', '/sportif/reserver/confirmer', [SportifController::class, 'storeBooking']);
    $router->add('GET',  '/sportif/coach-details', [SportifController::class, 'showCoachDetails']);
    $router->add('GET', '/sportif/coach-details', [SportifController::class, 'showCoachDetails']);
    $router->add('GET', '/sportif/coach-details', [SportifController::class, 'showCoachDetails']);
    $router->add('GET', '/sportif/profile', [SportifController::class, 'showProfile']);
    $router->add('GET', '/sportif/edit-profile', [SportifController::class, 'editProfile']);
    $router->add('POST', '/sportif/update-profile', [SportifController::class, 'updateProfile']);
    $router->add('POST', '/sportif/update-profile', [SportifController::class, 'updateProfile']);


    // Coach Routes
    $router->add('GET', '/coach/dashboard', [CoachController::class, 'index']);
    $router->add('GET', '/coach/profile', [CoachController::class, 'showProfile']);
    $router->add('GET', '/coach/edit', [CoachController::class, 'edit']);
    $router->add('POST', '/coach/update', [CoachController::class, 'update']);
    $router->add('GET', '/coach/availabilities', [CoachController::class, 'showAvailabilities']);
    $router->add('POST', '/coach/availabilities/add', [CoachController::class, 'addAvailability']);
    $router->add('POST', '/coach/reservation/traiter', [CoachController::class, 'handleReservation']);


    $uri = $_SERVER['REQUEST_URI'];
    $basePath = '/CoachProV3/public';

    if(strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }

    $uri = explode('?', $uri)[0];

    if (empty($uri)) $uri = '/';

    $router->dispatch($uri, $_SERVER['REQUEST_METHOD']);


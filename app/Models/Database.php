<?php 
    
    namespace App\Models;
    
    use PDO;
    use PDOException;

    class Database
    {
        private static $instance = null;

        public static function getConnection() {
            if (self::$instance === null) {

                $path = __DIR__ . '/../../config/database.php';

                if(!file_exists($path)) {
                    die('Error : Config file not found at ' . $path);
                }

                $config = require $path;

                try {
                    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
                    self::$instance = new PDO($dsn, $config['user'], $config['password']);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Connection failed : ". $e->getMessage());
                }
            }

            return self::$instance;
        }
    }

    
?>
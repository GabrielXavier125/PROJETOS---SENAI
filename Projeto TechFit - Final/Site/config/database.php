<?php
class Database {
    private static $instance = null;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=localhost;dbname=techfit_academia;charset=utf8mb4";
                self::$instance = new PDO(
                    $dsn,
                    "root",
                    "senaisp",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch (PDOException $e) {
                error_log("Erro de conexão com banco de dados: " . $e->getMessage());
                die("Erro de conexão com o banco de dados.");
            }
        }
        return self::$instance;
    }
}

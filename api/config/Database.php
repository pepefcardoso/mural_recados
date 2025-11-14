<?php

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (Exception $e) {
    die('Erro ao carregar o arquivo .env: ' . $e->getMessage());
}

class Database
{
    private $host = $_ENV['DB_HOST'];
    private $db_name = $_ENV['DB_DATABASE'];
    private $username = $_ENV['DB_USERNAME'];
    private $password = $_ENV['DB_PASSWORD'];
    private $conn;

    public function connect()
    {
        $this->conn = null;

        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8';
            $this->conn = new PDO($dsn, $this->username, $this->password);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro de Conexão: ' . $e->getMessage());
            return null;
        }

        return $this->conn;
    }
}

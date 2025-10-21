<?php
class Database
{
    private $host = 'localhost';
    private $port = '5432';
    private $db_name = 'inventario';
    private $username = 'postgres';
    private $password = '12345678';
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            echo "Error de conexión PostgreSQL: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

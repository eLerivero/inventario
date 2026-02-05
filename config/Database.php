<?php
class Database
{
    private $host = '127.0.0.1';
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

            // Configurar la codificación UTF-8 para PostgreSQL
            $this->conn->exec("SET client_encoding TO 'UTF8'");

            // Configurar atributos PDO
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Reemplazar appLog con error_log
            error_log('DEBUG: Conexión a base de datos establecida correctamente');
        } catch (PDOException $exception) {
            // Reemplazar appLog con error_log
            error_log('ERROR: Error de conexión PostgreSQL: ' . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

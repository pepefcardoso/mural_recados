<?php

class Recado
{
    private $conn;
    private $table = 'recados';
    public $id;
    public $mensagem;
    public $status;
    public $data_criacao;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function read()
    {
        $query = 'SELECT id, mensagem, status, data_criacao FROM ' . $this->table . ' ORDER BY status DESC, data_criacao DESC';

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function read_single()
    {
        $query = 'SELECT id, mensagem, status, data_criacao FROM ' . $this->table . ' WHERE id = :id LIMIT 1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' (mensagem) VALUES (:mensagem)';

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':mensagem', $this->mensagem);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function update()
    {
        $query = 'UPDATE ' . $this->table . ' SET mensagem = :mensagem WHERE id = :id';

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':mensagem', $this->mensagem);
        $stmt->bindParam(':id', $this->id);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function toggleFavorite()
    {
        $queryRead = 'SELECT status FROM ' . $this->table . ' WHERE id = :id';
        $stmtRead = $this->conn->prepare($queryRead);
        $stmtRead->bindParam(':id', $this->id);
        $stmtRead->execute();
        $row = $stmtRead->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $newStatus = $row['status'] == 1 ? 0 : 1;

        $queryUpdate = 'UPDATE ' . $this->table . ' SET status = :status WHERE id = :id';

        $stmtUpdate = $this->conn->prepare($queryUpdate);
        $stmtUpdate->bindParam(':status', $newStatus);
        $stmtUpdate->bindParam(':id', $this->id);

        try {
            $stmtUpdate->execute();
            return $newStatus;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}

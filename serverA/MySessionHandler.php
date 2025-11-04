<?php
class MySessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessionId) {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :sessionId LIMIT 1");
        $stmt -> execute(['sessionId' => $sessionId]);
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['data'];
        }
        return'';
    }

    public function write($sessionId, $data) {
        $stmt = $this->pdo->prepare('
            INSERT INTO sessions (id, data) VALUES (:sessionId, :data)
            ON DUPLICATE KEY UPDATE data = :data, last_access = NOW()
        ');
        return $stmt->execute(['sessionId'=> $sessionId,'data'=> $data]);
    }

    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function gc($maxlifetime) {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < (NOW() - INTERVAL :ml SECOND)");
        return $stmt->execute(['ml' => $maxlifetime]);
    }
}
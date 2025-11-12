<?php
class MySessionHandler implements SessionHandlerInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open(string $savePath, string $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $sessionId): string|false {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :sessionId LIMIT 1");
        $stmt->execute(['sessionId' => $sessionId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return (string)$row['data'];
        }
        return '';
    }

    public function write(string $sessionId, string $data): bool {
        $emptyForms = ['', 'a:0:{}', 'N;', 'b:0;'];
        if (in_array($data, $emptyForms, true)) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (id, data) VALUES (:sessionId, :data)'
            . ' ON DUPLICATE KEY UPDATE data = :data, last_access = NOW()'
        );
        return (bool)$stmt->execute(['sessionId' => $sessionId, 'data' => $data]);
    }

    public function destroy(string $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
        return (bool)$stmt->execute(['id' => $id]);
    }

    public function gc(int $max_lifetime): int|false {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < (NOW() - INTERVAL :ml SECOND)");
        if (!$stmt->execute(['ml' => $max_lifetime])) {
            return false;
        }
        return $stmt->rowCount();
    }
}

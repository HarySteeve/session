<?php

class HAProxyServer {
    public string $name;
    public string $host;
    public int $port;

    public function __construct(string $name, string $host, int $port = 3306) {
        $this->name = $name;
        $this->host = $host;
        $this->port = $port;
    }

    public function toLine(string $indent = '    '): string {
        return $indent . 'server ' . $this->name . ' ' . $this->host . ':' . $this->port . ' check';
    }
}

class HAProxyBackend {
    public string $name;
    public array $lines; // without section header
    public string $nl;

    public function __construct(string $name, array $lines, string $nl) {
        $this->name = $name;
        $this->lines = $lines;
        $this->nl = $nl;
    }

    public function getServers(): array {
        $servers = [];
        foreach ($this->lines as $line) {
            if (preg_match('/^\s*server\s+(\S+)\s+(\S+?)(?::(\d+))?\s+check\b/i', $line, $m)) {
                $port = isset($m[3]) && is_numeric($m[3]) ? (int)$m[3] : 3306;
                $servers[] = new HAProxyServer($m[1], $m[2], $port);
            }
        }
        return $servers;
    }

    public function hasServer(string $name): bool {
        foreach ($this->getServers() as $s) if ($s->name === $name) return true;
        return false;
    }

    public function setBalance(string $mode): void {
        $found = false;
        foreach ($this->lines as &$line) {
            if (preg_match('/^\s*balance\s+/i', $line)) {
                $line = preg_replace('/^\s*balance\s+\S+/i', "    balance $mode", $line);
                $found = true;
                break;
            }
        }
        
        unset($line);

        if (!$found) {
            $insertAt = 0;
            while (isset($this->lines[$insertAt]) && trim($this->lines[$insertAt]) === '') 
                $insertAt++;
            array_splice($this->lines, $insertAt, 0, ["    balance $mode"]);
        }
    }

    public function getBalance(): ?string {
        foreach ($this->lines as $line) {
            if (preg_match('/^\s*balance\s+(\S+)/i', $line, $m)) return $m[1];
        }
        return null;
    }

    public function addServer(HAProxyServer $server): void {
        if ($this->hasServer($server->name)) {
            throw new Exception("Le serveur {$server->name} existe déjà dans backend {$this->name}.");
        }

        $insertPos = count($this->lines);

        for ($i = count($this->lines) - 1; $i >= 0; $i--) {
            if (trim($this->lines[$i]) !== '') {
                $insertPos = $i + 1;
                break;
            }
        }
        array_splice($this->lines, $insertPos, 0, [$server->toLine()]);
    }

    public function deleteServer(string $name): bool {
        foreach ($this->lines as $i => $line) {
            if (preg_match('/^\s*server\s+' . preg_quote( $name, '/') . '\s+\S+(?::\d+)?\s+check\b/i', $line)) {
                array_splice($this->lines, $i, 1);
                return true;
            }
        }
        throw new Exception("Le serveur $name n'existe pas dans backend {$this->name}.");
    }

    public function updateServer(string $oldName, string $newName, string $newHost): bool {
        foreach ($this->lines as $i => $line) {
            if (preg_match('/^\s*server\s+' . preg_quote($oldName, '/') . '\s+\S+(?::\d+)?\s+check\b/i', $line)) {
                $host = $newHost;
                $port = 3306;
                if (preg_match('/^(.+?):(\d+)$/', $newHost, $m)) {
                    $host = $m[1];
                    $port = (int)$m[2];
                }
                $this->lines[$i] = '    server ' . $newName . ' ' . $host . ':' . $port . ' check';
                return true;
            }
        }
        throw new Exception("Le serveur $oldName n'existe pas dans backend {$this->name}.");
    }

    public function renderLines(): array {
        return $this->lines;
    }
}

class HAProxyConfig {
    private string $path;
    private string $raw;
    public string $nl;
    private array $lines;

    public function __construct(string $path) {
        $this->path = $path;
        $this->raw = file_get_contents($this->path);
        $this->nl = (strpos($this->raw, "\r\n") !== false) ? "\r\n" : "\n";
        $this->lines = explode($this->nl, $this->raw);
    }

    public function backup(string $dir): string {
        $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'haproxy_' . date('Ymd_His') . '.cfg';
        copy($this->path, $file);
        return $file;
    }

    private function findBackendRange(string $backendName): ?array {
        $start = null;
        $end = null;
        $total = count($this->lines);
        for ($i = 0; $i < $total; $i++) {
            if (preg_match('/^\s*backend\s+' . preg_quote($backendName, '/') . '\b/i', $this->lines[$i])) {
                $start = $i;
                // search end
                for ($j = $i + 1; $j < $total; $j++) {
                    if (preg_match('/^\s*(?:frontend|backend|listen|global|defaults)\b/i', $this->lines[$j])) {
                        $end = $j; break;
                    }
                }
                if ($end === null) 
                    $end = $total;
                return [$start, $end];
            }
        }
        return null;
    }

    public function getBackend(string $backendName): ?HAProxyBackend {
        $range = $this->findBackendRange($backendName);
        if ($range === null) {
            // Empty backend so callers can modify and then call setBackend -> save
            return new HAProxyBackend($backendName, [], $this->nl);
        }
        [$start, $end] = $range;
      
        $body = array_slice($this->lines, $start + 1, $end - $start - 1);
        return new HAProxyBackend($backendName, $body, $this->nl);
    }

    public function setBackend(HAProxyBackend $backend): void {
        $range = $this->findBackendRange($backend->name);
        $render = $backend->renderLines();
        if ($range === null) {
            // Append two newlines to preserve spacing (\n\n)
            $trimmed = rtrim(implode($this->nl, $this->lines), $this->nl);
            $this->lines = explode($this->nl, $trimmed);
            $this->lines[] = '';
            $this->lines[] = 'backend ' . $backend->name;
            foreach ($render as $ln) 
                $this->lines[] = $ln;
        } else {
            [$start, $end] = $range;
            
            $new = array_slice($this->lines, 0, $start + 1);
            foreach ($render as $ln) 
                $new[] = $ln;
            
            for ($i = $end; $i < count($this->lines); $i++) 
                $new[] = $this->lines[$i];

            $this->lines = $new;
        }
    }

    public function save(): void {
        $content = implode($this->nl, $this->lines);
        $res = @file_put_contents($this->path, $content);
        if ($res === false) 
            throw new Exception("Impossible d'ecrire le fichier de configuration: {$this->path}");
    }
}

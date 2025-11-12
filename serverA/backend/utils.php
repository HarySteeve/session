<?php
$configPath = __DIR__ . '/../haproxy/haproxy.cfg';
$backupDir = __DIR__ . '/../haproxy/backups';

if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);

// Sauvegarde
$backupFile = $backupDir . '/haproxy_' . date('Ymd_His') . '.cfg';
if (!copy($configPath, $backupFile)) die("❌ Échec de la sauvegarde de haproxy.cfg !");
echo "💾 Sauvegarde créée : $backupFile\n";

// Lecture des serveurs MySQL
function getAllMysqlServers() {
    global $configPath;
    $config = file_get_contents($configPath);
    preg_match_all('/server\s+(\S+)\s+(\S+):3306\s+check/', $config, $matches, PREG_SET_ORDER);

    $servers = [];
    foreach ($matches as $match) $servers[] = ['name' => $match[1], 'host' => $match[2]];
    return $servers;
}

// Ajout
function addMysqlServer($name, $host, $balanceMode = null) {
    global $configPath;
    $config = file_get_contents($configPath);

    if (strpos($config, "server $name ") !== false) return "Le serveur $name existe déjà.";

    // Met à jour le mode de balance si précisé
    if ($balanceMode) {
        $config = preg_replace(
            '/(backend mysql_servers[\s\S]*?balance )\w+/',
            '${1}' . $balanceMode,
            $config
        );
    }

    // Ajoute le serveur à la fin du backend
    $config = preg_replace(
        '/(backend mysql_servers[\s\S]*?)(\n$)/',
        "$1    server $name $host:3306 check\n",
        $config
    );

    file_put_contents($configPath, $config);
    shell_exec("docker exec haproxy service haproxy reload");

    return "✅ Serveur $name ajouté et HAProxy rechargé.";
}

// Suppression
function deleteMysqlServer($name) {
    global $configPath;
    $config = file_get_contents($configPath);

    $pattern = '/^\s*server\s+' . preg_quote($name, '/') . '\s+\S+:3306\s+check\s*$/m';
    $newConfig = preg_replace($pattern, '', $config);
    $newConfig = preg_replace("/^\s*\n/m", "", $newConfig); // supprime les lignes vides

    if ($newConfig === $config) return "Le serveur $name n’existe pas.";

    file_put_contents($configPath, $newConfig);
    shell_exec("docker exec haproxy service haproxy reload");

    return "✅ Serveur $name supprimé et HAProxy rechargé.";
}

// Modification
function updateMysqlServer($oldName, $newName, $newHost) {
    global $configPath;
    $config = file_get_contents($configPath);

    if (strpos($config, "server $oldName ") === false) return "Le serveur $oldName n’existe pas.";

    $pattern = '/server\s+' . preg_quote($oldName, '/') . '\s+\S+:3306\s+check/';
    $replacement = "server $newName $newHost:3306 check";
    $newConfig = preg_replace($pattern, $replacement, $config);

    file_put_contents($configPath, $newConfig);
    shell_exec("docker exec haproxy service haproxy reload");

    return "✅ Serveur $oldName mis à jour.";
}

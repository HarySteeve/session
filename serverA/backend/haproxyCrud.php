<?php
require_once __DIR__ . '/utils.php';

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'list':
        header('Content-Type: application/json');
        echo json_encode(getAllMysqlServers());
        break;

    case 'add':
        $name = $_POST['serverName'] ?? null;
        $host = $_POST['serverHost'] ?? null;
        $balance = $_POST['balanceMode'] ?? null;
        if (!$name || !$host) {
            echo "Nom ou hôte manquant.";
            exit;
        }
        echo addMysqlServer($name, $host, $balance);
        break;

    case 'delete':
        $name = $_POST['serverName'] ?? null;
        echo deleteMysqlServer($name);
        break;

    case 'update':
        $old = $_POST['oldName'] ?? null;
        $new = $_POST['newName'] ?? null;
        $host = $_POST['newHost'] ?? null;
        echo updateMysqlServer($old, $new, $host);
        break;

    default:
        echo "Action invalide.";
}

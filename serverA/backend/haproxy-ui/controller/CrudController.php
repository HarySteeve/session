<?php 
require_once __DIR__ . '/../model/HAproxyConfig.php';
require_once __DIR__ . '/CrudController.php';

$configPath = __DIR__ . '/../../../haproxy/haproxy.cfg';
$cfgObj = new HAProxyConfig($configPath);

$backupDir = __DIR__ . '/../../../haproxy/backups/';
if (!is_dir($backupDir)) 
    mkdir($backupDir, 0777, true);

$action = $_GET['action'] ?? null;
$targetBackend = $_POST['backend'] ?? $_GET['backend'] ?? 'mysql_servers';

header("content-type: application/json");
switch ($action) {
    case 'list':
        header('Content-Type: application/json');
        echo json_encode(getAllMysqlServers($targetBackend));
        break;

    case 'add':
        $name = $_POST['serverName'] ?? null;
        $host = $_POST['serverHost'] ?? null;
        $port = isset($_POST['serverPort']) && is_numeric($_POST['serverPort']) ? (int)$_POST['serverPort'] : null;
        $balance = $_POST['balanceMode'] ?? null;
        
        if (!$name || !$host) {
            returnErrorResponse("Nom ou hôte manquant");
            exit;
        }

        try {
            returnSuccessResponse(addServer($name, $host, $port, $balance, $targetBackend));
        } catch (Exception $ex) {
            returnErrorResponse($ex->getMessage());
        }

        break;

    case 'delete':
        $name = $_POST['serverName'] ?? null;

        try {
            returnSuccessResponse(deleteMysqlServer($name, $targetBackend));
        } catch (Exception $ex) {
            returnErrorResponse($ex->getMessage());
        }
        break;

    case 'update':
        $old = $_POST['oldName'] ?? null;
        $new = $_POST['newName'] ?? null;
        $host = $_POST['newHost'] ?? null;
        $newPort = isset($_POST['newPort']) && is_numeric($_POST['newPort']) ? (int)$_POST['newPort'] : null;
        
        try {
            returnSuccessResponse(updateMysqlServer($old, $new, $host, $newPort, $targetBackend));
        } catch (Exception $ex) {
            returnErrorResponse($ex->getMessage());
        }
        
        break;
 
    case 'get-balance':
        try {
            $mode = getBackendBalance($targetBackend);
            echo json_encode(["success" => true, "mode" => $mode]);
        } catch (Exception $ex) {
            returnErrorResponse($ex->getMessage());
        }
        break;
 
    case 'balance':
        $mode = $_POST['balanceMode'] ?? null;
        if (!$mode) {
            returnErrorResponse("Mode de balance manquant");
            exit;
        }
        try {
            setBackendBalance($mode, $targetBackend);
            returnSuccessResponse("Mode de balance defini: $mode");
        } catch (Exception $ex) {
            returnErrorResponse($ex->getMessage());
        }
        break;

    default:
        echo returnErrorResponse("Action invalide.");
}

function getAllMysqlServers($backendName = 'mysql_servers') {
    global $cfgObj;
    $backend = $cfgObj->getBackend($backendName);
    $servers = $backend->getServers();
    $out = [];
    foreach ($servers as $s)
        $out[] = ['name' => $s->name, 'host' => $s->host, 'port' => $s->port];
    return $out;
}

function addServer($name, $host, $port = null, $balanceMode = null, $backendName = 'mysql_servers') {
    global $cfgObj, $backupDir;
    
    $backupFile = $cfgObj->backup($backupDir);
    if (!file_exists($backupFile))
         throw new Exception("Echec de la sauvegarde avant ajout de serveur");
        
    $backend = $cfgObj->getBackend($backendName);
    if ($balanceMode) 
        $backend->setBalance($balanceMode);

    $portVal = is_numeric($port) ? (int)$port : 3306;
    $backend->addServer(new HAProxyServer($name, $host, $portVal));
    $cfgObj->setBackend($backend);
    $cfgObj->save();
    restartHaproxy();
    return true;
}

function deleteMysqlServer($name, $backendName = 'mysql_servers') {
    global $cfgObj, $backupDir;
    $backupFile = $cfgObj->backup($backupDir);
    
    if (!file_exists($backupFile)) 
        throw new Exception("Echec de la sauvegarde avant suppression de serveur");
    $backend = $cfgObj->getBackend($backendName);
    $backend->deleteServer($name);
    $cfgObj->setBackend($backend);
    $cfgObj->save();
    restartHaproxy();
    return true;
}

function updateMysqlServer($oldName, $newName, $newHost, ?int $newPort = null, $backendName = 'mysql_servers') {
    global $cfgObj, $backupDir;
    $backupFile = $cfgObj->backup($backupDir);
    if (!file_exists($backupFile)) 
        throw new Exception("Echec de la sauvegarde avant mise a jour de serveur");

    $backend = $cfgObj->getBackend($backendName);
    $backend->updateServer($oldName, $newName, $newHost, $newPort);
    $cfgObj->setBackend($backend);
    $cfgObj->save();
    restartHaproxy();
    return true;
}

function getBackendBalance($backend = 'mysql_servers') {
    global $cfgObj;
    $b = $cfgObj->getBackend($backend);
    return $b->getBalance();
}

function setBackendBalance($mode, $backend = 'mysql_servers') {
    global $cfgObj, $backupDir;
    $backupFile = $cfgObj->backup($backupDir);

    if (!file_exists($backupFile)) 
        throw new Exception("Echec de la sauvegarde avant changement du mode de balance.");

    $b = $cfgObj->getBackend($backend);
    $b->setBalance($mode);
    $cfgObj->setBackend($b);
    $cfgObj->save();
    restartHaproxy();
    return true;
}

/****************************
 * Utils
 ****************************/

function restartHaproxy() {
    shell_exec("docker exec haproxy service haproxy reload");
}

function returnSuccessResponse($message) {
    echo json_encode(["success" => true, "message" => $message]);
}

function returnErrorResponse($message) {
    echo json_encode(["success" => false, "message" => $message]);
}
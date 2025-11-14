<?php 
require_once __DIR__ . '/../model/HAproxyConfig.php';

$configPath = __DIR__ . '/../../../haproxy.cfg';
$cfgObj = new HAProxyConfig($configPath);

function getAllServers($backendName = 'mysql_servers') {
    global $cfgObj;
    $backend = $cfgObj->getBackend($backendName);
    $servers = $backend->getServers();
    $out = [];
    foreach ($servers as $s)
        $out[] = ['name' => $s->name, 'host' => $s->host, 'port' => $s->port];
    return $out;
}
<?php 
require_once __DIR__ . '/../model/HAproxyConfig.php';

$configPath = __DIR__ . '/../../../haproxy/haproxy.cfg';
$cfgObj = new HAProxyConfig($configPath);

function getAllMysqlServers() {
    global $cfgObj;
    $backend = $cfgObj->getBackend('mysql_servers');
    $servers = $backend->getServers();
    $out = [];
    foreach ($servers as $s) 
        $out[] = ['name' => $s->name, 'host' => $s->host];
    return $out;
}
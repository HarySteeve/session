<?php
// require_once __DIR__."/MySessionHandler.php";

// $pdo = new PDO("mysql:host=127.0.0.1;dbname=session;charset=utf8", "root", "");

// $handler = new MySessionHandler($pdo);
// session_set_save_handler($handler, true); 

session_start();

$couleur = $_POST["couleur"];
$_SESSION["couleur"] = $couleur;

header("location: index.php");
exit();
?>
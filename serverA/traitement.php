<?php
require_once __DIR__ . '/init.php';

$couleur = $_POST['couleur'] ?? null;
if ($couleur !== null) {
	$_SESSION['couleur'] = $couleur;
}

header('location: index.php');
exit();
?>
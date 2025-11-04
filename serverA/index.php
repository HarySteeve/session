<?php
    require_once __DIR__."/MySessionHandler.php";

    $pdo = new PDO("mysql:host=127.0.0.1;dbname=session;charset=utf8", "root", "");

    $handler = new MySessionHandler($pdo);
    session_set_save_handler($handler, true); 

    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>Server A</h3>
    <form action="traitement.php" method="post">
        <p>
            Votre couleur prefere: <input type="text" placeholder="bleu" name="couleur">
            <button>Valider</button>
        </p>
    </form>
    <br>
    <?php if(isset($_SESSION["couleur"])) { ?>
        <p>Votre couleur prefere est: <strong><?php echo $_SESSION["couleur"]; ?></strong></p>
    <?php } ?>
</body>
</html>
<?php
require_once __DIR__ . '/../backend/init.php';
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
    <form action="../backend/traitement.php" method="post">
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

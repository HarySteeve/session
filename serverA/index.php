<?php
    require_once __DIR__."/MySessionHandler.php";

    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASSWORD') ?: 'root';
    $dbName = getenv('DB_NAME') ?: 'session_db';

    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8";
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        echo "Database connection error";
        exit;
    }

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
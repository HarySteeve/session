<?php require_once __DIR__ . '/../backend/utils.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des serveurs MySQL HAProxy</title>
</head>
<body>
<h2>Liste des serveurs MySQL</h2>

<table border="1" cellpadding="5">
    <tr><th>Nom</th><th>Hôte</th><th>Action</th></tr>
    <?php foreach (getAllMysqlServers() as $srv): ?>
        <tr>
            <td><?= htmlspecialchars($srv['name']) ?></td>
            <td><?= htmlspecialchars($srv['host']) ?></td>
            <td>
                <form action="../backend/haproxyCrud.php?action=delete" method="post" style="display:inline;">
                    <input type="hidden" name="serverName" value="<?= htmlspecialchars($srv['name']) ?>">
                    <button type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<h3>Ajouter un serveur</h3>
<form action="../backend/haproxyCrud.php?action=add" method="post">
    <label>Nom du serveur: <input name="serverName" required></label><br>
    <label>Hôte du serveur: <input name="serverHost" required></label><br>
    <label>Mode de balance (optionnel): <input name="balanceMode"></label><br>
    <button type="submit">Ajouter</button>
</form>

<hr>

<h3>Modifier un serveur</h3>
<form action="../backend/haproxyCrud.php?action=update" method="post">
    <label>Ancien nom: <input name="oldName" required></label><br>
    <label>Nouveau nom: <input name="newName" required></label><br>
    <label>Nouvel hôte: <input name="newHost" required></label><br>
    <button type="submit">Mettre à jour</button>
</form>

</body>
</html>

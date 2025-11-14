<?php
require_once __DIR__ . '/../backend/haproxy-ui/controller/MainController.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des serveurs Web HAProxy</title>

    <style>
        .selected-row {
            background: #fffbcc;
        }

    </style>
</head>

<body>

    <div>
        <h2>Configuration serveurs web</h2>

        <div>
            <form action="../backend/haproxy-ui/controller/CrudController.php?action=balance" id="balanceForm">
                <label for="balanceMode">Mode de balance</label>
                <select id="balanceMode" name="balanceMode">
                    <option value="roundrobin">roundrobin</option>
                    <option value="leastconn">leastconn</option>
                    <option value="source">source</option>
                    <option value="uri">uri</option>
                    <option value="url_param">url_param</option>
                    <option value="hdr">hdr</option>
                    <option value="rdp-cookie">rdp-cookie</option>
                </select>
                <input type="hidden" name="backend" value="web_servers">
                <button>Changer</button>
            </form>
        </div>

        <script src="js/haproxy-common.js"></script>
        <script>
            ////////////////////////////////
            // CRUD balance
            ////////////////////////////////

            (async function loadBalanceMode() {
                try {
                    const resp = await fetch('../backend/haproxy-ui/controller/CrudController.php?action=get-balance&backend=web_servers');
                    const data = await resp.json();
                    if (data && data.mode) {
                        const sel = document.getElementById('balanceMode');
                        if (sel) 
                            sel.value = data.mode;
                    }
                } catch (err) {
                    alert('Erreur lors du chargement du mode de balance: ' + err.message);
                }
            })();

            const balanceForm = document.getElementById('balanceForm');
            if (balanceForm) {
                balanceForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(balanceForm);
                    const res = await fetchAndExpectResponse(await fetch(balanceForm.action, { method: 'POST', body: fd }));
                    if (res && res.success) 
                        location.reload();
                });
            }
        </script>

        <table border="1" id="table" cellpadding="5">
            <tr>
                <th>Nom</th>
                <th>Hôte</th>
                <th>Port</th>
                <th>Action</th>
            </tr>
            <?php foreach (getAllServers('web_servers') as $srv): ?>
                <tr>
                    <td><?= htmlspecialchars($srv['name']) ?></td>
                    <td><?= htmlspecialchars($srv['host']) ?></td>
                    <td><?= htmlspecialchars($srv['port'] ?? 80) ?></td>
                    <td>
                        <button type="button" class="modify-btn" data-name="<?= htmlspecialchars($srv['name']) ?>" data-host="<?= htmlspecialchars($srv['host']) ?>" data-port="<?= htmlspecialchars($srv['port'] ?? 80) ?>">Modifier</button>
                        <button type="button" class="delete-btn" data-name="<?= htmlspecialchars($srv['name']) ?>">Supprimer</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <hr>

    <div>
        <h3>Ajouter un serveur</h3>
        <form action="../backend/haproxy-ui/controller/CrudController.php?action=add" id="addServerForm" method="post">
            <input type="hidden" name="backend" value="web_servers">
            <label>Nom du serveur: <input name="serverName" value="web3" required></label><br>
            <label>Hôte du serveur: <input name="serverHost" value="server_3" required></label><br>
            <label>Port: <input name="serverPort" type="number" value="8000"></label><br>
            <button type="submit">Ajouter</button>
        </form>
    </div>

    <hr>

    <div>
        <h3>Modifier un serveur</h3>
        <form id="modifyServerForm" action="../backend/haproxy-ui/controller/CrudController.php?action=update" method="post">
            <input type="hidden" name="backend" value="web_servers">
            <label>Ancien nom: <input id="oldName" name="oldName" readonly required></label><br>
            <label>Nouveau nom: <input id="newName" name="newName" required></label><br>
            <label>Nouvel hôte: <input id="newHost" name="newHost" required></label><br>
            <label>Nouveau port: <input id="newPort" name="newPort" type="number" value="80"></label><br>
            <button type="submit" id="updateBtn">Confirmer la modification</button>
        </form>
    </div>

    <script>
        ////////////////////////////////
        // Add
        ////////////////////////////////
        
        const addForm = document.getElementById("addServerForm");
        addForm.addEventListener("submit", async (e)=> {
            e.preventDefault();
            await submitFormAndReload(addForm);
        });

        ////////////////////////////////
        // Modify
        ////////////////////////////////

        const oldNameInput = document.getElementById('oldName');
        const newNameInput = document.getElementById('newName');
        const newHostInput = document.getElementById('newHost');
        const newPortInput = document.getElementById('newPort');

        document.querySelectorAll('.modify-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const name = btn.dataset.name || '';
                const host = btn.dataset.host || '';

                oldNameInput.value = name;
                newNameInput.value = name;
                newHostInput.value = host;
                if (newPortInput) newPortInput.value = btn.dataset.port || 80;

                document.querySelectorAll('#table tr').forEach(r => r.classList.remove('selected-row'));
                const row = btn.closest('tr');
                if (row) row.classList.add('selected-row');

                document.getElementById('modifyServerForm').scrollIntoView({behavior: 'smooth', block: 'center'});
                newNameInput.focus();
            });
        });

        const modifyForm = document.getElementById("modifyServerForm");
        modifyForm.addEventListener("submit", async (e)=> {
            e.preventDefault();
            await submitFormAndReload(modifyForm);
        });

        ////////////////////////////////
        // Delete
        ////////////////////////////////

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const name = btn.dataset.name;
                if (!name) return;
                if (!confirm(`Supprimer le serveur "${name}" ?`)) return;

                const formData = new FormData();
                formData.append('serverName', name);
                formData.append('backend', 'web_servers');

                try {
                    const resp = await fetch('../backend/haproxy-ui/controller/CrudController.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await fetchAndExpectResponse(resp);
                    if (result && result.success) location.reload();
                } catch (err) {
                    alert('Erreur lors de la suppression ' + err);
                }
            });
        });
    </script>

</body>

</html>

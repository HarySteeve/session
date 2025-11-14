<?php
require_once __DIR__ . '/../backend/haproxy-ui/controller/MainController.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des serveurs MySQL HAProxy</title>

    <style>
        .selected-row {
            background: #fffbcc;
        }

    </style>
</head>

<body>

    <div>
        <h2>Configuration serveurs mysql</h2>

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
                <button>Changer</button>
            </form>
        </div>

        <script>
            ////////////////////////////////
            // CRUD balance
            ////////////////////////////////

            (async function loadBalanceMode() {
                try {
                    const resp = await fetch('../backend/haproxy-ui/controller/CrudController.php?action=get-balance');
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
                <th>Action</th>
            </tr>
            <?php foreach (getAllMysqlServers() as $srv): ?>
                <tr>
                    <td><?= htmlspecialchars($srv['name']) ?></td>
                    <td><?= htmlspecialchars($srv['host']) ?></td>
                    <td>
                        <button type="button" class="modify-btn" data-name="<?= htmlspecialchars($srv['name']) ?>" data-host="<?= htmlspecialchars($srv['host']) ?>">Modifier</button>
                        <button type="button" class="delete-btn" data-name="<?= htmlspecialchars($srv['name']) ?>">Supprimer</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <hr>

    <div>
        <h3>Ajouter un serveur</h3>
        <form action="../backend/haproxy-ui/controller/CrudController.php?action=add" id="addMysqlServerForm" method="post">
            <label>Nom du serveur: <input name="serverName" value="mysql3" required></label><br>
            <label>Hôte du serveur: <input name="serverHost" value="mysql_db_3" required></label><br>
            <button type="submit">Ajouter</button>
        </form>
    </div>

    <hr>

    <div>
        <h3>Modifier un serveur</h3>
        <form id="modifyMysqlServerForm" action="../backend/haproxy-ui/controller/CrudController.php?action=update" method="post">
            <label>Ancien nom: <input id="oldName" name="oldName" readonly required></label><br>
            <label>Nouveau nom: <input id="newName" name="newName" required></label><br>
            <label>Nouvel hôte: <input id="newHost" name="newHost" required></label><br>
            <button type="submit" id="updateBtn">Confirmer la modification</button>
        </form>
    </div>

    <script>
        async function fetchAndExpectResponse(response) {
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                if (data.success)
                    alert(`Succes: ${data.message}`);
                else
                    alert(`Erreur: ${data.message}`);
                return data;
            } catch (err) {
                alert('Non JSON response: ' + text);
                return { success: false, message: text };
            }
        }
    </script>

    <script>
        ////////////////////////////////
        // Add server
        ////////////////////////////////

        const addForm = document.getElementById("addMysqlServerForm");
        addForm.addEventListener("submit", async (e)=> {
            e.preventDefault();
            const formData = new FormData(addForm);

            const addResult = await fetchAndExpectResponse(await fetch(addForm.action, {
                method: "POST",
                body: formData
            }));
            if (addResult && addResult.success) {
                // reload to reflect new server
                location.reload();
            }
        });


        ////////////////////////////////
        // Server modification
        ////////////////////////////////

        const table = document.getElementById("table");
        
        const oldNameInput = document.getElementById('oldName');
        const newNameInput = document.getElementById('newName');
        const newHostInput = document.getElementById('newHost');
        const updateBtn = document.getElementById('updateBtn');

        document.querySelectorAll('.modify-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const name = btn.dataset.name || '';
                const host = btn.dataset.host || '';

                oldNameInput.value = name;
                newNameInput.value = name;
                newHostInput.value = host;

                // Highlight the selected row
                document.querySelectorAll('#table tr').forEach(r => r.classList.remove('selected-row'));
                const row = btn.closest('tr');
                if (row)
                    row.classList.add('selected-row');

                // Focus the new name
                document.getElementById('modifyMysqlServerForm').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                newNameInput.focus();
            });
        });

        ////////////////////////////////
        // Server deletion
        ////////////////////////////////

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const name = btn.dataset.name;
                if (!name) return;
                if (!confirm(`Supprimer le serveur "${name}" ?`)) 
                    return;

                const formData = new FormData();
                formData.append('serverName', name);

                try {
                    const resp = await fetch('../backend/haproxy-ui/controller/CrudController.php?action=delete', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await fetchAndExpectResponse(resp);
                    if (result && result.success) {
                        location.reload();
                    }
                } catch (err) {
                    alert('Erreur lors de la suppression ' + err);
                }
            });
        });

        const modifyForm = document.getElementById("modifyMysqlServerForm");
        modifyForm.addEventListener("submit", async (e)=> {
            e.preventDefault();
            const formData = new FormData(modifyForm);

            const res = await fetchAndExpectResponse(await fetch(modifyForm.action, {
                method: "POST",
                body: formData
            }));
            if (res && res.success) {
                location.reload();
            }
        });
    </script>

</body>

</html>
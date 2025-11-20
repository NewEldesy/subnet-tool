<?php session_start(); if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = session_id(); } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de sous-réseaux IPv4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <h1 class="text-center mb-4">Générateur de sous-réseaux IPv4</h1>
        
        <!-- Formulaire de génération -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Générer des sous-réseaux</h5>
                    </div>
                    <div class="card-body">
                        <form id="subnetForm">
                            <div class="mb-3">
                                <label for="ip" class="form-label">Adresse IP réseau</label>
                                <input type="text" class="form-control" id="ip" placeholder="192.168.1.0" required>
                            </div>
                            <div class="mb-3">
                                <label for="cidr" class="form-label">Préfixe CIDR (/xx)</label>
                                <input type="number" class="form-control" id="cidr" min="8" max="30" value="24" required>
                            </div>
                            <div class="mb-3">
                                <label for="numSubnets" class="form-label">Nombre de sous-réseaux</label>
                                <input type="number" class="form-control" id="numSubnets" min="1" max="256" value="2" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Générer</button>
                        </form>
                        <div id="results" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Historique -->
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Historique des générations (votre session)</h5>
                    </div>
                    <div class="card-body">
                        <div id="historyList" class="list-group"></div>
                        <div class="mt-2">
                            <button id="exportHistory" class="btn btn-success btn-sm me-2">Exporter CSV</button>
                            <button id="clearHistory" class="btn btn-danger btn-sm">Vider l'historique</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chargement initial de l'historique
        loadHistory();

        // Soumission du formulaire (AJAX + validation)
        document.getElementById('subnetForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            // Récupérer les valeurs directement depuis les champs
            const data = {
                ip: document.getElementById('ip').value,
                cidr: document.getElementById('cidr').value,
                numSubnets: document.getElementById('numSubnets').value
            };
            
            // Validation JS côté client
            if (!/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(data.ip)) {
                alert('Adresse IP invalide (format: xxx.xxx.xxx.xxx)');
                return;
            }
            data.cidr = '/' + data.cidr; // Format pour PHP

            try {
                const response = await fetch('generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    displayResults(result.subnets);
                    loadHistory(); // Rafraîchir l'historique
                } else {
                    alert('Erreur : ' + (result.error || 'Génération échouée'));
                }
            } catch (err) {
                alert('Erreur de connexion : ' + err.message);
            }
        });

        // Affichage des résultats
        function displayResults(subnets) {
            let html = '<div class="alert alert-success"><h6>Résultats :</h6><ul class="list-unstyled">';
            subnets.forEach(subnet => {
                html += `<li><strong>${subnet.network}</strong> (/${subnet.cidr}) - Plage : ${subnet.firstHost} - ${subnet.lastHost} (${subnet.hosts} hôtes)</li>`;
            });
            html += '</ul></div>';
            document.getElementById('results').innerHTML = html;
        }

        // Chargement de l'historique
        async function loadHistory() {
            try {
                const response = await fetch('history.php');
                const histories = await response.json();
                let html = '';
                histories.forEach(h => {
                    html += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${h.ip} /${h.cidr} → ${h.num_subnets} sous-réseaux (${new Date(h.created_at).toLocaleString('fr-FR')})</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewHistory(${h.id})">Voir</button>
                    </div>`;
                });
                document.getElementById('historyList').innerHTML = html || '<p class="text-muted">Aucun historique pour cette session.</p>';
            } catch (err) {
                console.error(err);
                document.getElementById('historyList').innerHTML = '<p class="text-danger">Erreur de chargement.</p>';
            }
        }

        // Affichage d'un historique spécifique
        async function viewHistory(id) {
            try {
                const response = await fetch(`view_history.php?id=${id}`);
                const data = await response.json();
                if (data.success) {
                    displayResults(data.subnets);
                } else {
                    alert('Erreur : ' + (data.error || 'Historique non trouvé'));
                }
            } catch (err) {
                alert('Erreur de chargement : ' + err.message);
            }
        }

        // Export CSV
        document.getElementById('exportHistory').addEventListener('click', async () => {
            try {
                const response = await fetch('export.php');
                if (!response.ok) throw new Error('Erreur export');
                const csv = await response.text();
                const a = document.createElement('a');
                a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
                a.download = `historique_sous-reseaux_${new Date().toISOString().split('T')[0]}.csv`;
                a.click();
            } catch (err) {
                alert('Erreur d\'export : ' + err.message);
            }
        });

        // Vidage de l'historique
        document.getElementById('clearHistory').addEventListener('click', async () => {
            if (confirm('Vider tout l\'historique de cette session ?')) {
                try {
                    const response = await fetch('clear_history.php', { method: 'POST' });
                    const result = await response.json();
                    if (result.success) {
                        loadHistory();
                        alert('Historique vidé.');
                    } else {
                        alert('Erreur : ' + (result.error || 'Échec vidage'));
                    }
                } catch (err) {
                    alert('Erreur de connexion : ' + err.message);
                }
            }
        });
    </script>
</body>
</html>
<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Filtres
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$sexe = isset($_GET['sexe']) ? $_GET['sexe'] : '';

// Construction de la requête avec filtres
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search OR e.matricule LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($filiere > 0) {
    $whereConditions[] = "f.id = :filiere";
    $params[':filiere'] = $filiere;
}

if (!empty($sexe)) {
    $whereConditions[] = "e.sexe = :sexe";
    $params[':sexe'] = $sexe;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Requête pour compter le total
$countQuery = "SELECT COUNT(*) as total 
               FROM inscriptions i
               JOIN etudiants e ON i.etudiant_id = e.id
               JOIN filieres f ON i.filiere_id = f.id
               $whereClause";

$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Requête principale
$query = "SELECT i.id as inscription_id, i.date_inscription,
                 e.id as etudiant_id, e.matricule, e.nom, e.prenom, e.sexe, e.email, e.telephone,
                 e.nationalite, e.date_naissance, e.lieu_naissance,
                 e.bac_serie, e.bac_annee, e.bac_mention,
                 f.nom as filiere_nom, f.code as filiere_code,
                 d.nom as departement_nom,
                 fac.nom as faculte_nom, fac.type as faculte_type,
                 p.mode_paiement, p.reference, p.montant
          FROM inscriptions i
          JOIN etudiants e ON i.etudiant_id = e.id
          JOIN filieres f ON i.filiere_id = f.id
          JOIN departements d ON f.departement_id = d.id
          JOIN facultes fac ON d.faculte_id = fac.id
          LEFT JOIN paiements p ON i.id = p.inscription_id
          $whereClause
          ORDER BY i.date_inscription DESC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$statsQuery = "SELECT 
                COUNT(*) as total,
                COUNT(matricule) as avec_matricule,
                COUNT(*) - COUNT(matricule) as sans_matricule,
                COUNT(DISTINCT CASE WHEN e.sexe = 'M' THEN e.id END) as hommes,
                COUNT(DISTINCT CASE WHEN e.sexe = 'F' THEN e.id END) as femmes
               FROM inscriptions i
               JOIN etudiants e ON i.etudiant_id = e.id";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Récupération des filières pour le filtre
$filieresQuery = "SELECT id, nom FROM filieres ORDER BY nom";
$filieresStmt = $db->prepare($filieresQuery);
$filieresStmt->execute();
$allFilieres = $filieresStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants - Université de Ngaoundéré</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           STYLE COMPLET - LISTE DES ÉTUDIANTS
           ============================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .liste-container {
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* En-tête */
        .liste-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px;
            border-radius: 12px 12px 0 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }

        .liste-header::before {
            content: '📊';
            position: absolute;
            font-size: 200px;
            opacity: 0.1;
            right: -50px;
            top: -50px;
            transform: rotate(15deg);
        }

        .liste-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .liste-header p {
            font-size: 1.1em;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #1e3c72;
        }

        .stat-label {
            color: #666;
            font-size: 0.85em;
            margin-top: 5px;
        }

        /* Barre d'outils */
        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-export {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Tableau */
        .table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .etudiants-table {
            width: 100%;
            border-collapse: collapse;
        }

        .etudiants-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .etudiants-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .etudiants-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }

        .etudiants-table tbody tr:hover {
            background: #f8f9fa;
            transition: background 0.3s ease;
        }

        /* Matricule */
        .matricule-cell {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            background: #f0f0f0;
            letter-spacing: 1px;
        }

        .matricule-badge {
            display: inline-block;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        /* Badges de statut */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-en-attente {
            background: #fff3cd;
            color: #856404;
        }

        .status-validee {
            background: #d4edda;
            color: #155724;
        }

        /* Boutons action */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .page-link {
            display: block;
            padding: 10px 15px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .page-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .page-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        /* Alertes */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideInRight 0.5s ease-out;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Bouton retour */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            font-weight: 600;
            width: 180px;
            color: #666;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .liste-header h1 {
                font-size: 1.5em;
            }
            
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                justify-content: space-between;
            }
            
            .etudiants-table th,
            .etudiants-table td {
                padding: 8px 10px;
                font-size: 0.85em;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="liste-container">
        <!-- Bouton retour -->
        <a href="index.php" class="back-button">
            ← Retour à l'accueil
        </a>
        
        <!-- En-tête -->
        <div class="liste-header">
            <h1>📋 Liste des Étudiants Inscrits</h1>
            <p>Université de Ngaoundéré - Année académique 2024-2025</p>
        </div>
        
        <!-- Messages de session -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Inscriptions</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-number"><?php echo $stats['avec_matricule']; ?></div>
                <div class="stat-label">Avec Matricule</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?php echo $stats['sans_matricule']; ?></div>
                <div class="stat-label">Sans Matricule</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👨</div>
                <div class="stat-number"><?php echo $stats['hommes']; ?></div>
                <div class="stat-label">Hommes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👩</div>
                <div class="stat-number"><?php echo $stats['femmes']; ?></div>
                <div class="stat-label">Femmes</div>
            </div>
        </div>
        
        <!-- Barre d'outils -->
        <div class="toolbar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Rechercher par nom, prénom, email ou matricule..." value="<?php echo htmlspecialchars($search); ?>">
                <span class="search-icon">🔍</span>
            </div>
            <div class="filter-group">
                <select id="filiereFilter" class="filter-select">
                    <option value="0">Toutes les filières</option>
                    <?php foreach($allFilieres as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $filiere == $f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="sexeFilter" class="filter-select">
                    <option value="">Tous les sexes</option>
                    <option value="M" <?php echo $sexe == 'M' ? 'selected' : ''; ?>>Masculin</option>
                    <option value="F" <?php echo $sexe == 'F' ? 'selected' : ''; ?>>Féminin</option>
                </select>
                
                <button id="exportBtn" class="btn-export">
                    📥 Exporter Excel
                </button>
            </div>
        </div>
        
        <!-- Tableau des étudiants -->
        <div class="table-wrapper">
            <div class="table-responsive">
                <table class="etudiants-table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom complet</th>
                            <th>Sexe</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Filière</th>
                            <th>Faculté</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </thead>
                        <tbody>
                            <?php if(count($etudiants) > 0): ?>
                                <?php foreach($etudiants as $etudiant): ?>
                                <tr>
                                    <td class="matricule-cell">
                                        <?php 
                                        if(!empty($etudiant['matricule'])) {
                                            $mat = $etudiant['matricule'];
                                            if(strlen($mat) >= 8) {
                                                $formatted = substr($mat, 0, 2) . ' ' . 
                                                            substr($mat, 2, 1) . ' ' . 
                                                            substr($mat, 3, 5) . ' ' . 
                                                            substr($mat, 8);
                                                echo "<span class='matricule-badge'>$formatted</span>";
                                            } else {
                                                echo "<span class='matricule-badge'>" . htmlspecialchars($mat) . "</span>";
                                            }
                                        } else {
                                            echo "<span style='color: orange;'>En attente</span>";
                                        }
                                        ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']); ?></strong></td>
                                    <td><?php echo $etudiant['sexe'] == 'M' ? 'Masculin' : 'Féminin'; ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['telephone']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['filiere_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($etudiant['faculte_nom']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($etudiant['date_inscription'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewDetails(<?php echo $etudiant['inscription_id']; ?>)">
                                                👁️ Détails
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        🚫 Aucun étudiant trouvé
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
                <ul class="pagination">
                    <li class="page-item">
                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&filiere=<?php echo $filiere; ?>&sexe=<?php echo urlencode($sexe); ?>">
                            « Début
                        </a>
                    </li>
                    <?php if($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filiere=<?php echo $filiere; ?>&sexe=<?php echo urlencode($sexe); ?>">
                                ‹ Précédent
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item">
                            <a class="page-link <?php echo $i == $page ? 'active' : ''; ?>" 
                               href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filiere=<?php echo $filiere; ?>&sexe=<?php echo urlencode($sexe); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filiere=<?php echo $filiere; ?>&sexe=<?php echo urlencode($sexe); ?>">
                                Suivant ›
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&filiere=<?php echo $filiere; ?>&sexe=<?php echo urlencode($sexe); ?>">
                            Fin »
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Modal pour les détails -->
        <div id="detailsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>📄 Détails de l'étudiant</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Contenu chargé dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button class="btn-action" style="background: #6c757d; color: white;" onclick="closeModal()">Fermer</button>
                </div>
            </div>
        </div>
        
        <script>
            // Fonctions pour les filtres
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if(e.key === 'Enter') {
                    applyFilters();
                }
            });
            
            document.getElementById('filiereFilter').addEventListener('change', applyFilters);
            document.getElementById('sexeFilter').addEventListener('change', applyFilters);
            
            function applyFilters() {
                const search = document.getElementById('searchInput').value;
                const filiere = document.getElementById('filiereFilter').value;
                const sexe = document.getElementById('sexeFilter').value;
                
                window.location.href = `liste.php?page=1&search=${encodeURIComponent(search)}&filiere=${filiere}&sexe=${encodeURIComponent(sexe)}`;
            }
            
            // Export Excel
            document.getElementById('exportBtn').addEventListener('click', function() {
                const search = document.getElementById('searchInput').value;
                const filiere = document.getElementById('filiereFilter').value;
                const sexe = document.getElementById('sexeFilter').value;
                
                window.location.href = `export_excel.php?search=${encodeURIComponent(search)}&filiere=${filiere}&sexe=${encodeURIComponent(sexe)}`;
            });
            
            // Voir les détails
            function viewDetails(inscriptionId) {
                fetch(`get_etudiant_details.php?id=${inscriptionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            const e = data.data;
                            const modalBody = document.getElementById('modalBody');
                            modalBody.innerHTML = `
                                <div class="detail-row">
                                    <div class="detail-label">Matricule :</div>
                                    <div class="detail-value"><strong>${e.matricule || 'Non généré'}</strong></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Nom complet :</div>
                                    <div class="detail-value">${e.nom} ${e.prenom}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Sexe :</div>
                                    <div class="detail-value">${e.sexe === 'M' ? 'Masculin' : 'Féminin'}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Nationalité :</div>
                                    <div class="detail-value">${e.nationalite}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Date de naissance :</div>
                                    <div class="detail-value">${new Date(e.date_naissance).toLocaleDateString('fr-FR')}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Lieu de naissance :</div>
                                    <div class="detail-value">${e.lieu_naissance}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email :</div>
                                    <div class="detail-value">${e.email}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Téléphone :</div>
                                    <div class="detail-value">${e.telephone}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Baccalauréat :</div>
                                    <div class="detail-value">Série ${e.bac_serie} - ${e.bac_annee} (${e.bac_mention})</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Filière :</div>
                                    <div class="detail-value">${e.filiere_nom}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Département :</div>
                                    <div class="detail-value">${e.departement_nom}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Faculté/École :</div>
                                    <div class="detail-value">${e.faculte_nom}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Mode de paiement :</div>
                                    <div class="detail-value">${e.mode_paiement || 'Non renseigné'}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Référence paiement :</div>
                                    <div class="detail-value">${e.reference || 'Non renseigné'}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Date d'inscription :</div>
                                    <div class="detail-value">${new Date(e.date_inscription).toLocaleString('fr-FR')}</div>
                                </div>
                            `;
                            document.getElementById('detailsModal').classList.add('show');
                        }
                    });
            }
            
            function closeModal() {
                document.getElementById('detailsModal').classList.remove('show');
            }
            
            // Fermer le modal en cliquant en dehors
            window.onclick = function(event) {
                const modal = document.getElementById('detailsModal');
                if (event.target === modal) {
                    closeModal();
                }
            }
        </script>
    </body>
    </html>
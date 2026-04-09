<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupération des filtres
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$filiere = isset($_GET['filiere']) ? (int)$_GET['filiere'] : 0;
$sexe = isset($_GET['sexe']) ? $_GET['sexe'] : '';

// Construction de la requête avec filtres
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(e.nom LIKE :search OR e.prenom LIKE :search OR e.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status) && $status != 'all') {
    $whereConditions[] = "i.statut = :status";
    $params[':status'] = $status;
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

$query = "SELECT 
            e.id as 'ID',
            e.nom as 'Nom',
            e.prenom as 'Prénom',
            e.sexe as 'Sexe',
            e.nationalite as 'Nationalité',
            e.date_naissance as 'Date de naissance',
            e.lieu_naissance as 'Lieu de naissance',
            e.email as 'Email',
            e.telephone as 'Téléphone',
            e.bac_serie as 'Série Bac',
            e.bac_annee as 'Année Bac',
            e.bac_mention as 'Mention Bac',
            f.nom as 'Filière',
            d.nom as 'Département',
            fac.nom as 'Faculté/École',
            i.statut as 'Statut inscription',
            i.date_inscription as 'Date d\'inscription',
            p.mode_paiement as 'Mode de paiement',
            p.reference as 'Référence paiement',
            p.montant as 'Montant'
          FROM inscriptions i
          JOIN etudiants e ON i.etudiant_id = e.id
          JOIN filieres f ON i.filiere_id = f.id
          JOIN departements d ON f.departement_id = d.id
          JOIN facultes fac ON d.faculte_id = fac.id
          LEFT JOIN paiements p ON i.id = p.inscription_id
          $whereClause
          ORDER BY i.date_inscription DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// En-têtes pour le téléchargement Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=etudiants_preinscrits_' . date('Y-m-d') . '.csv');

// Création du fichier CSV
$output = fopen('php://output', 'w');

// Ajout du BOM pour UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Entêtes des colonnes
if (!empty($etudiants)) {
    fputcsv($output, array_keys($etudiants[0]), ';');
    
    // Données
    foreach ($etudiants as $etudiant) {
        fputcsv($output, $etudiant, ';');
    }
}

fclose($output);
exit();
?>
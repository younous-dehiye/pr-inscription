<?php
require_once 'config/database.php';

if(isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT 
                i.id as inscription_id, i.statut, i.date_inscription,
                e.id as etudiant_id, e.nom, e.prenom, e.sexe, e.email, e.telephone,
                e.nationalite, e.date_naissance, e.lieu_naissance,
                e.bac_serie, e.bac_annee, e.bac_mention,
                f.nom as filiere_nom, f.code as filiere_code,
                d.nom as departement_nom,
                fac.nom as faculte_nom, fac.type as faculte_type,
                p.mode_paiement, p.reference, p.montant, p.statut as paiement_statut
              FROM inscriptions i
              JOIN etudiants e ON i.etudiant_id = e.id
              JOIN filieres f ON i.filiere_id = f.id
              JOIN departements d ON f.departement_id = d.id
              JOIN facultes fac ON d.faculte_id = fac.id
              LEFT JOIN paiements p ON i.id = p.inscription_id
              WHERE i.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_GET['id']);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $etudiant]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Étudiant non trouvé']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
}
?>
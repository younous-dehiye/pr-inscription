<?php
// generate_matricules.php - Génère les matricules pour les étudiants existants
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Génération des matricules pour les étudiants existants</h2>";

try {
    // Récupérer tous les étudiants qui n'ont pas encore de matricule
    $query = "SELECT e.id, e.nom, e.prenom, i.filiere_id, e.date_inscription 
              FROM etudiants e
              JOIN inscriptions i ON e.id = i.etudiant_id
              WHERE e.matricule IS NULL OR e.matricule = ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($etudiants);
    
    if($total == 0) {
        echo "<p style='color: green;'>✅ Tous les étudiants ont déjà un matricule !</p>";
    } else {
        echo "<p>📊 Nombre d'étudiants sans matricule : <strong>$total</strong></p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr style='background: #667eea; color: white;'>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Matricule généré</th>
                <th>Statut</th>
              </tr>";
        
        $compte = 0;
        foreach($etudiants as $etudiant) {
            // Récupérer le code de la faculté
            $faculteQuery = "SELECT fac.code 
                             FROM filieres f 
                             JOIN departements d ON f.departement_id = d.id 
                             JOIN facultes fac ON d.faculte_id = fac.id 
                             WHERE f.id = :filiere_id";
            $faculteStmt = $db->prepare($faculteQuery);
            $faculteStmt->bindParam(':filiere_id', $etudiant['filiere_id']);
            $faculteStmt->execute();
            $faculte = $faculteStmt->fetch(PDO::FETCH_ASSOC);
            $faculteCode = $faculte['code'];
            
            // Par défaut, tous les étudiants sont en Licence (B)
            $cycleCode = 'B';
            
            // Année d'inscription (2 derniers chiffres)
            $annee = date('y', strtotime($etudiant['date_inscription']));
            
            // Compter le nombre d'étudiants déjà inscrits cette année-là
            $compteQuery = "SELECT COUNT(*) as total 
                            FROM etudiants 
                            WHERE YEAR(date_inscription) = YEAR(:date_inscription)
                            AND id <= :id";
            $compteStmt = $db->prepare($compteQuery);
            $compteStmt->bindParam(':date_inscription', $etudiant['date_inscription']);
            $compteStmt->bindParam(':id', $etudiant['id']);
            $compteStmt->execute();
            $result = $compteStmt->fetch(PDO::FETCH_ASSOC);
            $numero = str_pad($result['total'], 5, '0', STR_PAD_LEFT);
            
            // Générer le matricule
            $matricule = $annee . $cycleCode . $numero . $faculteCode;
            
            // Mettre à jour l'étudiant
            $updateQuery = "UPDATE etudiants SET matricule = :matricule WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':matricule', $matricule);
            $updateStmt->bindParam(':id', $etudiant['id']);
            
            if($updateStmt->execute()) {
                echo "<tr style='background: #d4edda;'>
                        <td>{$etudiant['id']}</td>
                        <td>{$etudiant['nom']}</td>
                        <td>{$etudiant['prenom']}</td>
                        <td><strong>$matricule</strong></td>
                        <td style='color: green;'>✓ Généré</td>
                      </tr>";
                $compte++;
            }
        }
        
        echo "</table>";
        echo "<p style='margin-top: 20px;'><strong>✅ $compte matricules générés avec succès !</strong></p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}

echo "<br><a href='liste.php' style='display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Voir la liste des étudiants</a>";
?>
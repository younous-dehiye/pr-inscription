<?php
session_start();
require_once 'config/database.php';

/**
 * Fonction pour générer le matricule
 * Format: AANNCCCCCF (ex: 22B606FS)
 * - AA: Année (2 derniers chiffres)
 * - N: Cycle (B=Licence, M=Master, D=Doctorat)
 * - CCCCC: Numéro séquentiel (5 chiffres)
 * - F: Code faculté (2-5 lettres)
 */
function genererMatricule($db, $filiere_id, $date_inscription) {
    try {
        // 1. Récupérer le code de la faculté
        $faculteQuery = "SELECT fac.code 
                         FROM filieres f 
                         JOIN departements d ON f.departement_id = d.id 
                         JOIN facultes fac ON d.faculte_id = fac.id 
                         WHERE f.id = :filiere_id";
        $faculteStmt = $db->prepare($faculteQuery);
        $faculteStmt->bindParam(':filiere_id', $filiere_id);
        $faculteStmt->execute();
        $faculte = $faculteStmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$faculte) {
            throw new Exception("Faculté non trouvée pour cette filière");
        }
        
        $faculteCode = $faculte['code'];
        
        // 2. Cycle par défaut (B = Licence)
        // Pour l'instant tous les étudiants sont en Licence
        // Plus tard on pourra ajouter un choix de cycle dans le formulaire
        $cycleCode = 'B';
        
        // 3. Année d'inscription (2 derniers chiffres)
        $annee = date('y', strtotime($date_inscription));
        
        // 4. Compter le nombre d'étudiants déjà inscrits cette année
        $compteQuery = "SELECT COUNT(*) as total 
                        FROM etudiants 
                        WHERE YEAR(date_inscription) = YEAR(:date_inscription)";
        $compteStmt = $db->prepare($compteQuery);
        $compteStmt->bindParam(':date_inscription', $date_inscription);
        $compteStmt->execute();
        $result = $compteStmt->fetch(PDO::FETCH_ASSOC);
        $numero = str_pad($result['total'] + 1, 5, '0', STR_PAD_LEFT);
        
        // 5. Générer le matricule
        $matricule = $annee . $cycleCode . $numero . $faculteCode;
        
        return $matricule;
        
    } catch(Exception $e) {
        throw new Exception("Erreur lors de la génération du matricule : " . $e->getMessage());
    }
}

// Vérification que le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Démarrer une transaction
        $db->beginTransaction();
        
        // 1. Vérifier si l'email existe déjà
        $checkQuery = "SELECT id FROM etudiants WHERE email = :email";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':email', $_POST['email']);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            throw new Exception("Un étudiant avec cet email existe déjà ! Veuillez utiliser un autre email.");
        }
        
        // 2. Insertion de l'étudiant (sans matricule d'abord)
        $query = "INSERT INTO etudiants (nom, prenom, sexe, nationalite, date_naissance, 
                  lieu_naissance, email, telephone, bac_serie, bac_annee, bac_mention) 
                  VALUES (:nom, :prenom, :sexe, :nationalite, :date_naissance, 
                  :lieu_naissance, :email, :telephone, :bac_serie, :bac_annee, :bac_mention)";
        
        $stmt = $db->prepare($query);
        
        // Nettoyer et sécuriser les données
        $nom = htmlspecialchars(trim($_POST['nom']));
        $prenom = htmlspecialchars(trim($_POST['prenom']));
        $sexe = $_POST['sexe'];
        $nationalite = htmlspecialchars(trim($_POST['nationalite']));
        $date_naissance = $_POST['date_naissance'];
        $lieu_naissance = htmlspecialchars(trim($_POST['lieu_naissance']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $telephone = preg_replace('/[^0-9]/', '', $_POST['telephone']);
        $bac_serie = $_POST['bac_serie'];
        $bac_annee = (int)$_POST['bac_annee'];
        $bac_mention = $_POST['bac_mention'];
        
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':sexe', $sexe);
        $stmt->bindParam(':nationalite', $nationalite);
        $stmt->bindParam(':date_naissance', $date_naissance);
        $stmt->bindParam(':lieu_naissance', $lieu_naissance);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':bac_serie', $bac_serie);
        $stmt->bindParam(':bac_annee', $bac_annee);
        $stmt->bindParam(':bac_mention', $bac_mention);
        
        $stmt->execute();
        $etudiant_id = $db->lastInsertId();
        
        // 3. Générer le matricule pour le nouvel étudiant
        $date_inscription = date('Y-m-d H:i:s');
        $matricule = genererMatricule($db, $_POST['filiere'], $date_inscription);
        
        // 4. Mettre à jour l'étudiant avec son matricule
        $updateQuery = "UPDATE etudiants SET matricule = :matricule WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':matricule', $matricule);
        $updateStmt->bindParam(':id', $etudiant_id);
        $updateStmt->execute();
        
        // 5. Insertion de l'inscription
        $query = "INSERT INTO inscriptions (etudiant_id, filiere_id) 
                  VALUES (:etudiant_id, :filiere_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':etudiant_id', $etudiant_id);
        $stmt->bindParam(':filiere_id', $_POST['filiere']);
        $stmt->execute();
        $inscription_id = $db->lastInsertId();
        
        // 6. Insertion du paiement
        $query = "INSERT INTO paiements (inscription_id, mode_paiement, reference, montant) 
                  VALUES (:inscription_id, :mode_paiement, :reference, :montant)";
        $stmt = $db->prepare($query);
        $montant = 5000.00;
        $stmt->bindParam(':inscription_id', $inscription_id);
        $stmt->bindParam(':mode_paiement', $_POST['mode_paiement']);
        $stmt->bindParam(':reference', $_POST['reference_paiement']);
        $stmt->bindParam(':montant', $montant);
        $stmt->execute();
        
        // Valider la transaction
        $db->commit();
        
        // 7. Récupérer les informations complètes pour l'affichage
        $infoQuery = "SELECT e.id, e.matricule, e.nom, e.prenom, e.email, e.telephone,
                             f.nom as filiere_nom, f.code as filiere_code,
                             d.nom as departement_nom,
                             fac.nom as faculte_nom, fac.code as faculte_code,
                             c.code as cycle_code, c.libelle as cycle_libelle
                      FROM etudiants e
                      JOIN inscriptions i ON e.id = i.etudiant_id
                      JOIN filieres f ON i.filiere_id = f.id
                      JOIN departements d ON f.departement_id = d.id
                      JOIN facultes fac ON d.faculte_id = fac.id
                      LEFT JOIN cycles c ON f.cycle_id = c.id
                      WHERE e.id = :etudiant_id";
        $infoStmt = $db->prepare($infoQuery);
        $infoStmt->bindParam(':etudiant_id', $etudiant_id);
        $infoStmt->execute();
        $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
        // Formater le matricule pour l'affichage
        $matricule_formate = $matricule;
        if(strlen($matricule) >= 8) {
            $matricule_formate = substr($matricule, 0, 2) . ' ' . 
                                 substr($matricule, 2, 1) . ' ' . 
                                 substr($matricule, 3, 5) . ' ' . 
                                 substr($matricule, 8);
        }
        
        // Créer le message de succès
        $message = "
            <div style='text-align: center;'>
                <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
                            color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
                    <h2 style='margin: 0;'>✅ Félicitations !</h2>
                    <p style='margin: 10px 0 0 0;'>Votre inscription a été acceptée avec succès</p>
                </div>
                
                <div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
                    <h3 style='color: #856404; margin: 0 0 10px 0;'>📝 VOTRE MATRICULE</h3>
                    <div style='font-size: 2.5em; font-weight: bold; font-family: monospace; 
                                letter-spacing: 3px; color: #1e3c72;'>
                        $matricule_formate
                    </div>
                    <div style='margin-top: 10px; font-size: 0.9em; color: #856404;'>
                        Année: 20" . substr($matricule, 0, 2) . " | 
                        Cycle: " . ($info['cycle_libelle'] ?? 'Licence') . " (" . substr($matricule, 2, 1) . ") | 
                        N°: " . substr($matricule, 3, 5) . " | 
                        Faculté: " . substr($matricule, 8) . "
                    </div>
                </div>
                
                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
                            gap: 15px; margin-bottom: 20px; text-align: left;'>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>
                        <strong>👤 Informations personnelles</strong><br>
                        Nom: {$info['nom']} {$info['prenom']}<br>
                        Email: {$info['email']}<br>
                        Téléphone: {$info['telephone']}
                    </div>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>
                        <strong>🏛️ Parcours académique</strong><br>
                        Faculté: {$info['faculte_nom']}<br>
                        Département: {$info['departement_nom']}<br>
                        Filière: {$info['filiere_nom']}
                    </div>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>
                        <strong>💰 Paiement</strong><br>
                        Mode: {$_POST['mode_paiement']}<br>
                        Référence: {$_POST['reference_paiement']}<br>
                        Montant: 5 000 FCFA
                    </div>
                </div>
                
                <div style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                    <strong>ℹ️ Information importante :</strong><br>
                    Veuillez conserver votre matricule. Il vous sera demandé pour toutes vos démarches administratives.
                </div>
            </div>
        ";
        
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = "success";
        
    } catch(Exception $e) {
        // Annuler la transaction en cas d'erreur
        $db->rollBack();
        
        // Message d'erreur
        $error_message = "
            <div style='text-align: center;'>
                <div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>
                    <h3 style='margin: 0;'>❌ Erreur lors de l'inscription</h3>
                </div>
                <p style='color: #721c24;'>" . $e->getMessage() . "</p>
                <p style='margin-top: 20px;'>Veuillez vérifier vos informations et réessayer.</p>
            </div>
        ";
        
        $_SESSION['message'] = $error_message;
        $_SESSION['message_type'] = "error";
    }
    
    // Redirection vers la page d'accueil
    header("Location: index.php");
    exit();
} else {
    // Si quelqu'un accède directement à ce fichier sans soumettre le formulaire
    header("Location: inscription.php");
    exit();
}
?>
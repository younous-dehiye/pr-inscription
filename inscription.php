<?php 
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-inscription - Université de Ngaoundéré</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Université de Ngaoundéré</h1>
            <p>Formulaire de Pré-inscription 2024-2025</p>
        </div>
        
        <div class="content">
            <?php
            if(isset($_SESSION['message'])) {
                echo '<div class="alert alert-' . $_SESSION['message_type'] . '">';
                echo $_SESSION['message'];
                echo '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>
            
            <form id="inscriptionForm" action="traitement.php" method="POST">
                <!-- Section Informations personnelles -->
                <div class="form-section">
                    <h2>📋 Informations personnelles</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required 
                                   placeholder="Entrez votre nom">
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" required 
                                   placeholder="Entrez votre prénom">
                        </div>
                        
                        <div class="form-group">
                            <label for="sexe">Sexe *</label>
                            <select id="sexe" name="sexe" required>
                                <option value="">Sélectionnez</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="nationalite">Nationalité *</label>
                            <input type="text" id="nationalite" name="nationalite" required 
                                   placeholder="Ex: Camerounaise">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_naissance">Date de naissance *</label>
                            <input type="date" id="date_naissance" name="date_naissance" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="lieu_naissance">Lieu de naissance *</label>
                            <input type="text" id="lieu_naissance" name="lieu_naissance" required 
                                   placeholder="Ville de naissance">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="exemple@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="telephone">Téléphone *</label>
                            <input type="tel" id="telephone" name="telephone" 
                                   pattern="[0-9]{9}" required 
                                   placeholder="6XXXXXXXX (9 chiffres)">
                            <small>Format: 6XXXXXXXX (9 chiffres)</small>
                        </div>
                    </div>
                </div>
                
                <!-- Section Baccalauréat -->
                <div class="form-section">
                    <h2>🎓 Informations Baccalauréat</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="bac_serie">Série du Bac *</label>
                            <select id="bac_serie" name="bac_serie" required>
                                <option value="">Sélectionnez</option>
                                <option value="A">A</option>
                                <option value="A1">A1</option>
                                <option value="A2">A2</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="TI">TI</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="bac_annee">Année d'obtention *</label>
                            <input type="number" id="bac_annee" name="bac_annee" 
                                   min="2000" max="2024" required 
                                   placeholder="2024">
                        </div>
                        
                        <div class="form-group">
                            <label for="bac_mention">Mention *</label>
                            <select id="bac_mention" name="bac_mention" required>
                                <option value="">Sélectionnez</option>
                                <option value="Passable">Passable</option>
                                <option value="Assez Bien">Assez Bien</option>
                                <option value="Bien">Bien</option>
                                <option value="Très Bien">Très Bien</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Section Choix filière -->
                <div class="form-section">
                    <h2>🏛️ Choix de la filière</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="faculte">Faculté/École *</label>
                            <select id="faculte" name="faculte" required>
                                <option value="">Sélectionnez une faculté/école</option>
                                <?php
                                $database = new Database();
                                $db = $database->getConnection();
                                $query = "SELECT id, nom, type FROM facultes ORDER BY type, nom";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $prefix = ($row['type'] == 'École') ? '🏫 ' : '📚 ';
                                    echo "<option value='".$row['id']."'>".$prefix.$row['nom']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="departement">Département *</label>
                            <select id="departement" name="departement" disabled required>
                                <option value="">Sélectionnez d'abord une faculté</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filiere">Filière souhaitée *</label>
                            <select id="filiere" name="filiere" disabled required>
                                <option value="">Sélectionnez d'abord un département</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Section Paiement -->
                <div class="form-section">
                    <h2>💰 Paiement des frais d'inscription</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="mode_paiement">Mode de paiement *</label>
                            <select id="mode_paiement" name="mode_paiement" required>
                                <option value="">Sélectionnez un mode de paiement</option>
                                <option value="Express Union">Express Union</option>
                                <option value="CCA Bank">CCA Bank</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="reference_paiement">Référence de paiement *</label>
                            <input type="text" id="reference_paiement" name="reference_paiement" 
                                   required placeholder="Entrez la référence fournie par l'agence">
                            <small>Montant à payer: 5000 FCFA</small>
                        </div>
                    </div>
                    <div id="paiement_info" style="margin-top: 15px;"></div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn-submit">
                        ✅ Soumettre ma pré-inscription
                    </button>
                    <button type="reset" class="btn-submit" style="background: #6c757d; margin-left: 10px;">
                        ↺ Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
<?php 
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Université de Ngaoundéré</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏛️ Université de Ngaoundéré</h1>
            <p>Bienvenue sur la plateforme de pré-inscription en ligne</p>
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
            
            <div class="form-section" style="text-align: center;">
                <h2>📝 Procédez à votre pré-inscription</h2>
                <p style="margin: 20px 0;">Cliquez sur le bouton ci-dessous pour commencer votre pré-inscription</p>
                <a href="inscription.php" class="btn-submit" style="display: inline-block; text-decoration: none; margin-right: 15px;">
                    🎓 Commencer ma pré-inscription
                </a>
                <a href="liste.php" class="btn-submit" style="display: inline-block; text-decoration: none; background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);">
                    📋 Voir la liste des étudiants
                </a>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
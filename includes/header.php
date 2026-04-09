<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-inscription - Université de Ngaoundéré</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php
    session_start();
    if(isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message_type'] . '">';
        echo $_SESSION['message'];
        echo '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    ?>
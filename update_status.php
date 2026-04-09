<?php
session_start();
require_once 'config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $inscription_id = $_POST['inscription_id'];
    $statut = $_POST['statut'];
    
    $query = "UPDATE inscriptions SET statut = :statut WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':statut', $statut);
    $stmt->bindParam(':id', $inscription_id);
    
    if($stmt->execute()) {
        $_SESSION['message'] = "Statut mis à jour avec succès !";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Erreur lors de la mise à jour du statut.";
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: liste.php");
    exit();
}
?>
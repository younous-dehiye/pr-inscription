<?php
require_once 'config/database.php';

if(isset($_GET['departement_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, nom FROM filieres WHERE departement_id = :departement_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':departement_id', $_GET['departement_id']);
    $stmt->execute();
    
    $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($filieres);
}
?>
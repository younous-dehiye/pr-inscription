<?php
require_once 'config/database.php';

if(isset($_GET['faculte_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, nom FROM departements WHERE faculte_id = :faculte_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':faculte_id', $_GET['faculte_id']);
    $stmt->execute();
    
    $departements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($departements);
}
?>
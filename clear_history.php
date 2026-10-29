<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? '';
if (empty($user_id)) {
    echo json_encode(['success' => false, 'error' => 'Session invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM history WHERE user_id = ?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur BDD : ' . $e->getMessage()]);
}
?>
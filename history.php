<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? '';
if (empty($user_id)) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, ip, cidr, num_subnets, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $histories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($histories);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur BDD : ' . $e->getMessage()]);
}
?>
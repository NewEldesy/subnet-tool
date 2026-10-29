<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? '';
if (empty($user_id) || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT results FROM history WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode([
            'success' => true,
            'subnets' => json_decode($row['results'], true)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Historique non trouvé']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur BDD : ' . $e->getMessage()]);
}
?>
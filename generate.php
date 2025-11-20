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

$input = json_decode(file_get_contents('php://input'), true);
$ip = filter_var($input['ip'] ?? '', FILTER_VALIDATE_IP);
$cidrStr = $input['cidr'] ?? '';
$numSubnets = (int)($input['numSubnets'] ?? 0);

if (!$ip || !preg_match('/^\/(\d+)$/', $cidrStr, $matches) || $numSubnets < 1) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

$cidr = (int)$matches[1];
if ($cidr < 8 || $cidr > 30) {
    echo json_encode(['success' => false, 'error' => 'CIDR invalide (8-30)']);
    exit;
}

// Fonction pour calculer les sous-réseaux
function generateSubnets($network, $cidr, $numSubnets) {
    $subnets = [];
    $networkLong = ip2long($network);
    $maskLong = ~((1 << (32 - $cidr)) - 1);
    $networkLong &= $maskLong;

    $bitsNeeded = ceil(log($numSubnets, 2));
    $newCidr = $cidr + $bitsNeeded;
    if ($newCidr > 32) {
        throw new Exception('Nombre de sous-réseaux trop élevé pour cet espace (/'.$cidr.' ne permet que jusqu\'à '.pow(2, 32 - $cidr).' sous-réseaux).');
    }
    $subnetSize = 1 << (32 - $newCidr);

    for ($i = 0; $i < $numSubnets; $i++) {
        $subnetLong = $networkLong + ($i * $subnetSize);
        if ($subnetLong > 0xFFFFFFFF) { // Éviter overflow
            throw new Exception('Overflow d\'adresse : espace insuffisant.');
        }
        $subnetIp = long2ip($subnetLong);
        $firstHost = long2ip($subnetLong + 1);
        $lastHost = long2ip($subnetLong + $subnetSize - 2);
        $numHosts = $subnetSize - 2;

        $subnets[] = [
            'addresse Réseau' => $subnetIp . '/' . $newCidr,
            'cidr' => $newCidr,
            'premiere hote' => $firstHost,
            'derniere hote' => $lastHost,
            'nombre d\'hotes' => $numHosts
        ];
    }
    return $subnets;
}

try {
    $subnets = generateSubnets($ip, $cidr, $numSubnets);

    // Stockage en BDD
    $stmt = $pdo->prepare("INSERT INTO history (ip, cidr, num_subnets, user_id, results) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ip, $cidr, $numSubnets, $user_id, json_encode($subnets)]);

    echo json_encode(['success' => true, 'subnets' => $subnets]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur BDD : ' . $e->getMessage()]);
}
?>
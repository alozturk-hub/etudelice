<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfAvisModel.php';

header('Content-Type: application/json; charset=utf-8');

$cuisinierId = isset($_GET['cuisinier_id']) ? (int) $_GET['cuisinier_id'] : 0;

if ($cuisinierId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cuisinier invalide.']);
    exit;
}

try {
    $pdo = connexionPDO();
    $avisModel = new TfAvisModel($pdo);

    $payload = [
        'avis_stats' => $avisModel->getCuisinierRatingSummary($cuisinierId),
        'avis_recents' => $avisModel->getRecentReviewsForCuisinier($cuisinierId, 5),
    ];

    if (isset($_SESSION['user_id']) && (int) ($_SESSION['user_role'] ?? 0) === 1) {
        $payload['avis_client'] = $avisModel->getReviewByClientAndCuisinier((int) $_SESSION['user_id'], $cuisinierId);
        $eligibility = $avisModel->canClientReviewCuisinier((int) $_SESSION['user_id'], $cuisinierId);
        $payload['avis_autorise'] = $eligibility['allowed'];
    }

    echo json_encode([
        'success' => true,
        'data' => $payload,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
    ]);
}

?>

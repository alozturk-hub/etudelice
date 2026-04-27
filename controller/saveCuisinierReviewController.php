<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfAvisModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

if ((int) ($_SESSION['user_role'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Seuls les clients peuvent laisser un avis.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$cuisinierId = isset($payload['cuisinier_id']) ? (int) $payload['cuisinier_id'] : 0;
$note = isset($payload['note']) ? (int) $payload['note'] : 0;
$commentaire = trim((string) ($payload['commentaire'] ?? ''));

if ($cuisinierId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cuisinier invalide.']);
    exit;
}

try {
    $pdo = connexionPDO();
    $avisModel = new TfAvisModel($pdo);
    $avisId = $avisModel->saveReview((int) $_SESSION['user_id'], $cuisinierId, $note, $commentaire);

    echo json_encode([
        'success' => true,
        'message' => 'Votre avis a bien été enregistré.',
        'avis_id' => $avisId,
        'avis_stats' => $avisModel->getCuisinierRatingSummary($cuisinierId),
        'avis_client' => $avisModel->getReviewByClientAndCuisinier((int) $_SESSION['user_id'], $cuisinierId),
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

?>

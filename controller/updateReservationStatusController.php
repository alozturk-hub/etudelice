<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfReservationModel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté.'
    ]);
    exit;
}

if ((int) ($_SESSION['user_role'] ?? 0) !== 2) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Seuls les cuisiniers peuvent modifier le statut.'
    ]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$reservationId = isset($payload['reservation_id']) ? (int) $payload['reservation_id'] : 0;

if ($reservationId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de commande invalide.'
    ]);
    exit;
}

try {
    $pdo = connexionPDO();
    $reservationModel = new TfReservationModel($pdo);

    $reservation = $reservationModel->getReservationById($reservationId);

    if (!$reservation) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Commande introuvable.'
        ]);
        exit;
    }

    if ((int) $reservation['user_id_1'] !== (int) $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Cette commande ne vous est pas assignée.'
        ]);
        exit;
    }

    if ((int) $reservation['reservation_statut'] >= 3) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cette commande est deja au dernier statut.'
        ]);
        exit;
    }

    $updated = $reservationModel->advanceReservationStatusForCuisinier($reservationId, (int) $_SESSION['user_id']);

    if (!$updated) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de mettre a jour le statut.'
        ]);
        exit;
    }

    $updatedReservation = $reservationModel->getReservationById($reservationId);
    $updatedReservation['plats'] = $reservationModel->getReservationPlats($reservationId);

    echo json_encode([
        'success' => true,
        'message' => 'Statut mis a jour avec succes.',
        'data' => $updatedReservation
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

?>

<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfReservationModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de réservation manquant ou invalide.']);
    exit;
}

$reservationId = (int) $_GET['id'];

try {
    $pdo = connexionPDO();
    $reservationModel = new TfReservationModel($pdo);

    $reservation = $reservationModel->getReservationById($reservationId);

    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée.']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];
    $userRole = (int) ($_SESSION['user_role'] ?? 0);

    $isClientOwner = (int) $reservation['user_id'] === $userId;
    $isAssignedCuisinier = $userRole === 2 && (int) $reservation['user_id_1'] === $userId;

    if (!$isClientOwner && !$isAssignedCuisinier) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé à cette réservation.']);
        exit;
    }

    // Ajouter les plats pour la réservation
    $plats = $reservationModel->getReservationPlats($reservationId);
    $reservation['plats'] = $plats;

    echo json_encode([
        'success' => true,
        'data' => $reservation
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

?>

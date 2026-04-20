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

try {
    $pdo = connexionPDO();
    $reservationModel = new TfReservationModel($pdo);

    $reservations = $reservationModel->getClientReservations($_SESSION['user_id']);

    // Ajouter les plats pour chaque réservation
    foreach ($reservations as &$reservation) {
        $plats = $reservationModel->getReservationPlats($reservation['reservation_id']);
        $reservation['plats'] = $plats;
        
        // Calculer le prix total de la réservation
        $total = 0;
        foreach ($plats as $plat) {
            $total += $plat['plat_prix'] * $plat['plat_reservation_quantite'];
        }
        $reservation['prix_total'] = $total;
    }

    echo json_encode([
        'success' => true,
        'data' => $reservations
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

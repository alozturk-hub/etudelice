<?php

session_start();

error_log('[RESERVATION] Début du traitement de la création de réservation');

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfReservationModel.php';

header('Content-Type: application/json; charset=utf-8');

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour créer une réservation'
    ]);
    exit;
}

try {
    // Récupérer les données JSON du corps de la requête
    $input = file_get_contents('php://input');
    error_log('[RESERVATION] Données reçues: ' . $input);
    
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Format JSON invalide');
    }

    // Validation des données
    if (empty($data['cuisinierId'])) {
        throw new Exception('ID du cuisinier manquant');
    }

    if (empty($data['plats']) || !is_array($data['plats'])) {
        throw new Exception('Plats manquants ou format invalide');
    }

    if (count($data['plats']) === 0) {
        throw new Exception('Au moins un plat est requis');
    }

    // Récupérer les données de session
    $userId = $_SESSION['user_id'];
    $cuisinierId = intval($data['cuisinierId']);

    // Valider que le cuisinier ne peut pas commander de lui-même
    if ($userId === $cuisinierId) {
        throw new Exception('Vous ne pouvez pas commander vos propres plats');
    }

    // Connexion à la base de données
    $pdo = connexionPDO();
    error_log('[RESERVATION] Connexion à la base de données établie');

    $model = new TfReservationModel($pdo);

    // Préparer les plats
    $plats = [];
    foreach ($data['plats'] as $plat) {
        if (empty($plat['id'])) {
            throw new Exception('ID de plat invalide');
        }
        $plats[] = [
            'id' => intval($plat['id']),
            'quantite' => isset($plat['quantite']) ? intval($plat['quantite']) : 1
        ];
    }

    error_log('[RESERVATION] Création de réservation - User: ' . $userId . ', Cuisiner: ' . $cuisinierId . ', Plats: ' . json_encode($plats));

    // Créer la réservation
    $reservationId = $model->createReservation($userId, $cuisinierId, $plats);

    if ($reservationId === false) {
        throw new Exception('Erreur lors de la création de la réservation');
    }

    // Récupérer les détails de la réservation créée
    $reservation = $model->getReservationById($reservationId);
    $reservationPlats = $model->getReservationPlats($reservationId);

    error_log('[RESERVATION] Réservation créée avec succès - ID: ' . $reservationId);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Réservation créée avec succès',
        'data' => [
            'reservation_id' => $reservationId,
            'reservation' => $reservation,
            'plats' => $reservationPlats
        ]
    ]);

} catch (Exception $e) {
    error_log('[RESERVATION] Erreur: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>

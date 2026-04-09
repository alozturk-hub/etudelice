<?php
session_start();
require_once '../modele/etudeliceDataBase.php';
require_once '../modele/taReservationModel.php';

header('Content-Type: application/json');

try {
    error_log('[GET_RESERVATIONS] Starting...');

    // 🔐 Vérifier utilisateur connecté
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non connecté'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];
    error_log('[GET_RESERVATIONS] User ID: ' . $userId);

    // 🗄️ Connexion BDD
    $pdo = connexionPDO();
    error_log('[GET_RESERVATIONS] DB connected');

    $reservationModel = new TaReservationModel($pdo);

    // 📥 Récupération des réservations du user
    $reservations = $reservationModel->getReservationsByUserId($userId);
    error_log('[GET_RESERVATIONS] Found ' . count($reservations) . ' reservations');

    // 🧹 Nettoyage des données (comme ton code)
    foreach ($reservations as &$res) {
        foreach ($res as $key => $value) {
            if (is_string($value)) {
                $res[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }
    }

    // 📤 Réponse JSON
    $response = json_encode([
        'success' => true,
        'data' => $reservations,
        'count' => count($reservations)
    ], JSON_UNESCAPED_UNICODE);

    if ($response === false) {
        error_log('[GET_RESERVATIONS] JSON encode failed: ' . json_last_error_msg());
        echo json_encode([
            'success' => false,
            'message' => 'Erreur d\'encodage JSON'
        ]);
    } else {
        error_log('[GET_RESERVATIONS] JSON response length: ' . strlen($response));
        echo $response;
    }

} catch (Exception $e) {
    error_log('[GET_RESERVATIONS] ERROR: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
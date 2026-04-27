<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';
require_once '../modele/tfReservationModel.php';
require_once '../modele/tfCuisinierPlatModel.php';
require_once '../modele/tfAvisModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

try {
    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);
    $reservationModel = new TfReservationModel($pdo);
    $platModel = new TfCuisinierPlatModel($pdo);
    $avisModel = new TfAvisModel($pdo);

    $user = $userModel->getUserById($_SESSION['user_id']);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
        exit;
    }

    unset($user['user_password']);
    $user['stats'] = $reservationModel->getReservationStatsForUser((int) $_SESSION['user_id']);

    if ((int) $user['role_id'] === 2) {
        $user['mes_plats'] = $platModel->getPlatsForProfile((int) $_SESSION['user_id']);
        $user['avis_stats'] = $avisModel->getCuisinierRatingSummary((int) $_SESSION['user_id']);
        $user['avis_recents'] = $avisModel->getRecentReviewsForCuisinier((int) $_SESSION['user_id'], 5);
        foreach ($user['mes_plats'] as &$plat) {
            if (!empty($plat['plat_image'])) {
                $plat['plat_image'] = 'data:image/jpeg;base64,' . base64_encode($plat['plat_image']);
            }
        }
    } else {
        $user['frigo'] = $platModel->getUserIngredients((int) $_SESSION['user_id']);
    }

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

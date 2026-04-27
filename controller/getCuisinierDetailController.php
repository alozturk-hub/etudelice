<?php
session_start();
require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';
require_once '../modele/tfAvisModel.php';

header('Content-Type: application/json');

try {
    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);
    $avisModel = new TfAvisModel($pdo);

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID du cuisinier manquant ou invalide'
        ]);
        exit;
    }

    $cuisinierId = (int) $_GET['id'];
    $cuisinier = $userModel->getUserById($cuisinierId);

    if (!$cuisinier || $cuisinier['role_id'] != 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Cuisinier non trouvé'
        ]);
        exit;
    }

    // Ne pas retourner le mot de passe
    unset($cuisinier['user_password']);
    $cuisinier['avis_stats'] = $avisModel->getCuisinierRatingSummary($cuisinierId);
    $cuisinier['avis_recents'] = $avisModel->getRecentReviewsForCuisinier($cuisinierId, 5);

    echo json_encode([
        'success' => true,
        'data' => $cuisinier
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>

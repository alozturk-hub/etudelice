<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

try {
    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);

    $user = $userModel->getUserById($_SESSION['user_id']);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
        exit;
    }

    unset($user['user_password']);

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}

<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $phrase = trim((string) ($data['phrase'] ?? ''));
    $expectedPhrase = 'je supprime mon compte etudelice';

    if ($phrase !== $expectedPhrase) {
        throw new Exception('La phrase de vérification est incorrecte.');
    }

    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);
    $userModel->deleteUserAccount((int) $_SESSION['user_id']);

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Votre compte a été supprimé.',
        'redirect' => 'index.html'
    ]);
} catch (Throwable $e) {
    error_log('[DELETE_ACCOUNT] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

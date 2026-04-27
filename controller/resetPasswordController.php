<?php

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Jeton manquant.']);
            exit;
        }

        $reset = $userModel->getValidPasswordResetByToken($token);

        if (!$reset) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lien invalide ou expiré.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Jeton valide.',
            'user_mail' => $reset['user_mail']
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = trim((string) ($payload['token'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $passwordConfirm = (string) ($payload['password_confirm'] ?? '');

    if ($token === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Jeton manquant.']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.']);
        exit;
    }

    if ($password !== $passwordConfirm) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.']);
        exit;
    }

    $reset = $userModel->getValidPasswordResetByToken($token);

    if (!$reset) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lien invalide ou expiré.']);
        exit;
    }

    $pdo->beginTransaction();
    $userModel->updatePasswordById((int) $reset['user_id'], $password);
    $userModel->markPasswordResetAsUsed((int) $reset['reset_id']);
    $userModel->clearPasswordResetTokensForUser((int) $reset['user_id']);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Votre mot de passe a bien été réinitialisé.',
        'redirect' => 'index.html'
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

?>

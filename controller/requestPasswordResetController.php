<?php

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

header('Content-Type: application/json; charset=utf-8');

function buildBaseUrl()
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(str_replace('/controller', '', $scriptDir), '/');

    return $scheme . '://' . $host . $basePath;
}

function sendPasswordResetEmail($email, $firstName, $resetLink)
{
    $subject = 'Réinitialisation de votre mot de passe Étudélice';
    $headline = 'Réinitialisation de votre mot de passe Étudélice';
    $messageBody = "Bonjour {$firstName},\n\nNous avons reçu une demande de réinitialisation de mot de passe pour votre compte Étudélice.\n\nCliquez sur ce lien pour choisir un nouveau mot de passe :\n{$resetLink}\n\nCe lien expire dans 1 heure.\nSi vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.\n\nL'équipe Étudélice";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: Etudelice <no-reply@etudelice.local>',
        'Reply-To: no-reply@etudelice.local',
        'X-Mailer: PHP/' . phpversion()
    ];

    $encodedSubject = mb_encode_mimeheader($headline, 'UTF-8');
    $sent = mail($email, $encodedSubject, $messageBody, implode("\r\n", $headers));

    if (!$sent) {
        return [
            'sent' => false,
            'simulated' => true,
            'message' => 'Email non envoyé par le serveur local. Utilisez le lien ci-dessous pour tester la réinitialisation.',
        ];
    }

    return [
        'sent' => true,
        'simulated' => false,
        'message' => 'Un email de réinitialisation a été envoyé si le compte existe.',
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string) ($payload['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
    exit;
}

try {
    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);
    $user = $userModel->getUserByEmail($email);

    $genericMessage = 'Si un compte correspond à cette adresse, un lien de réinitialisation a été préparé.';

    if (!$user) {
        echo json_encode(['success' => true, 'message' => $genericMessage]);
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $userModel->createPasswordResetToken((int) $user['user_id'], $tokenHash, $expiresAt);

    $resetLink = buildBaseUrl() . '/reset-password.html?token=' . urlencode($token);
    $mailStatus = sendPasswordResetEmail($user['user_mail'], $user['user_prenom'], $resetLink);

    $response = [
        'success' => true,
        'message' => $genericMessage,
        'notification' => $mailStatus,
    ];

    if (!empty($mailStatus['simulated'])) {
        $response['reset_link'] = $resetLink;
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

?>

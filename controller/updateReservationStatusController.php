<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfReservationModel.php';

header('Content-Type: application/json; charset=utf-8');

function sendReservationStatusEmail($reservation)
{
    $status = (int) ($reservation['reservation_statut'] ?? 0);
    $clientEmail = trim((string) ($reservation['client_mail'] ?? ''));
    $clientFirstName = trim((string) ($reservation['client_prenom'] ?? ''));
    $reservationId = (int) ($reservation['reservation_id'] ?? 0);

    if ($clientEmail === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('[STATUS_MAIL] Adresse email client invalide pour la reservation #' . $reservationId);
        return [
            'sent' => false,
            'simulated' => true,
            'message' => 'Notification client simulée : adresse email invalide ou absente.'
        ];
    }

    if ($status === 2) {
        $subject = 'Votre commande a ete acceptee !';
        $headline = 'Votre commande a ete acceptee !';
        $messageBody = "Bonjour {$clientFirstName},\n\nVotre commande #{$reservationId} a ete acceptee par votre cuisinier.\nVous pouvez suivre son avancement depuis votre espace Étudélice.\n\nA tres bientot,\nL'equipe Étudélice";
    } elseif ($status === 3) {
        $subject = 'Votre commande est prete !';
        $headline = 'Votre commande est prete !';
        $messageBody = "Bonjour {$clientFirstName},\n\nVotre commande #{$reservationId} est prete.\nVous pouvez consulter le detail de votre commande depuis votre espace Étudélice.\n\nBon appetit,\nL'equipe Étudélice";
    } else {
        return [
            'sent' => false,
            'simulated' => false,
            'message' => ''
        ];
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: Etudelice <no-reply@etudelice.local>',
        'Reply-To: no-reply@etudelice.local',
        'X-Mailer: PHP/' . phpversion()
    ];

    $encodedSubject = mb_encode_mimeheader($headline, 'UTF-8');
    $sent = mail($clientEmail, $encodedSubject, $messageBody, implode("\r\n", $headers));

    if (!$sent) {
        error_log('[STATUS_MAIL] Echec envoi mail pour la reservation #' . $reservationId . ' vers ' . $clientEmail);
        return [
            'sent' => false,
            'simulated' => true,
            'message' => 'Notification client simulée : le serveur local ne permet pas l’envoi réel d’email.'
        ];
    }

    return [
        'sent' => true,
        'simulated' => false,
        'message' => 'Email envoyé au client pour l’informer du changement de statut.'
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté.'
    ]);
    exit;
}

if ((int) ($_SESSION['user_role'] ?? 0) !== 2) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Seuls les cuisiniers peuvent modifier le statut.'
    ]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$reservationId = isset($payload['reservation_id']) ? (int) $payload['reservation_id'] : 0;

if ($reservationId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de commande invalide.'
    ]);
    exit;
}

try {
    $pdo = connexionPDO();
    $reservationModel = new TfReservationModel($pdo);

    $reservation = $reservationModel->getReservationById($reservationId);

    if (!$reservation) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Commande introuvable.'
        ]);
        exit;
    }

    if ((int) $reservation['user_id_1'] !== (int) $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Cette commande ne vous est pas assignée.'
        ]);
        exit;
    }

    if ((int) $reservation['reservation_statut'] >= 3) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Cette commande est deja au dernier statut.'
        ]);
        exit;
    }

    $updated = $reservationModel->advanceReservationStatusForCuisinier($reservationId, (int) $_SESSION['user_id']);

    if (!$updated) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de mettre a jour le statut.'
        ]);
        exit;
    }

    $updatedReservation = $reservationModel->getReservationById($reservationId);
    $updatedReservation['plats'] = $reservationModel->getReservationPlats($reservationId);
    $mailResult = sendReservationStatusEmail($updatedReservation);

    echo json_encode([
        'success' => true,
        'message' => 'Statut mis a jour avec succes.',
        'data' => $updatedReservation,
        'notification' => $mailResult
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

?>

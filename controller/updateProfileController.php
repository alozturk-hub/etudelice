<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Format JSON invalide.');
    }

    $nom = isset($data['nom']) ? trim($data['nom']) : '';
    $prenom = isset($data['prenom']) ? trim($data['prenom']) : '';
    $mail = isset($data['mail']) ? trim($data['mail']) : '';
    $telephone = isset($data['telephone']) ? trim($data['telephone']) : '';
    $specialite = isset($data['specialite']) ? trim($data['specialite']) : null;
    $password = isset($data['password']) ? $data['password'] : null;

    if ($nom === '' || $prenom === '' || $mail === '' || $telephone === '') {
        throw new Exception('Tous les champs obligatoires doivent être remplis.');
    }

    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide.');
    }

    $pdo = connexionPDO();
    $userModel = new TfUserModel($pdo);

    $updated = $userModel->updateUser(
        $_SESSION['user_id'],
        $nom,
        $prenom,
        $mail,
        $telephone,
        $specialite,
        $password
    );

    if (!$updated) {
        throw new Exception('Impossible de mettre à jour le profil.');
    }

    $_SESSION['user_nom'] = $nom;
    $_SESSION['user_prenom'] = $prenom;
    $_SESSION['user_mail'] = $mail;

    echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

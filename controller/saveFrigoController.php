<?php

session_start();

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfCuisinierPlatModel.php';

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

    if (!is_array($data) || !isset($data['ingredients']) || !is_array($data['ingredients'])) {
        throw new Exception('Liste d\'ingrédients invalide.');
    }

    $pdo = connexionPDO();
    $platModel = new TfCuisinierPlatModel($pdo);
    $platModel->replaceUserIngredients((int) $_SESSION['user_id'], $data['ingredients']);

    echo json_encode([
        'success' => true,
        'message' => 'Frigo mis à jour avec succès.'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

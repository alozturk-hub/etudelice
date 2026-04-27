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

if (!isset($_SESSION['user_id']) || (int) ($_SESSION['user_role'] ?? 0) !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Réservé aux cuisiniers connectés.']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception('Format JSON invalide.');
    }

    $libelle = trim((string) ($data['libelle'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $prix = isset($data['prix']) ? (float) $data['prix'] : 0;
    $ingredients = $data['ingredients'] ?? [];

    if ($libelle === '' || $description === '') {
        throw new Exception('Le libellé et la description sont obligatoires.');
    }

    if ($prix <= 0) {
        throw new Exception('Le prix doit être supérieur à 0.');
    }

    $pdo = connexionPDO();
    $platModel = new TfCuisinierPlatModel($pdo);
    $platId = $platModel->createPlatForCuisinier((int) $_SESSION['user_id'], $data);
    $plat = $platModel->getOwnedPlatById($platId, (int) $_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Plat ajouté avec succès.',
        'data' => $plat
    ]);
} catch (Throwable $e) {
    error_log('[CREATE_CUISINIER_PLAT] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

<?php
session_start();
require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfCuisinierPlatModel.php';

header('Content-Type: application/json');

try {
    error_log('[GET_PLATS] Starting...');
    $pdo = connexionPDO();
    error_log('[GET_PLATS] DB connected');
    $platModel = new TfCuisinierPlatModel($pdo);
    $cuisinierId = isset($_GET['cuisinier_id']) ? (int) $_GET['cuisinier_id'] : 0;
    $clientId = (isset($_SESSION['user_id']) && (int) ($_SESSION['user_role'] ?? 0) === 1)
        ? (int) $_SESSION['user_id']
        : null;

    if ($cuisinierId <= 0) {
        throw new Exception('ID du cuisinier manquant');
    }

    $plats = $platModel->getPlatsForCuisinier($cuisinierId, $clientId);
    error_log('[GET_PLATS] Found ' . count($plats) . ' plats');

    // Nettoyer les données pour éviter les problèmes d'encodage JSON
    foreach ($plats as &$plat) {
        foreach ($plat as $key => $value) {
            if ($key === 'ingredients' || $key === 'ingredients_disponibles') {
                continue;
            }
            if (is_string($value) || $value === null) {
                if ($key === 'plat_image' && !empty($value)) {
                    // Encoder l'image BLOB en base64 pour l'affichage
                    $plat[$key] = 'data:image/jpeg;base64,' . base64_encode($value);
                } elseif (is_string($value)) {
                    $plat[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
        }
    }

    $response = json_encode([
        'success' => true,
        'data' => $plats,
        'count' => count($plats)
    ], JSON_UNESCAPED_UNICODE);
    
    if ($response === false) {
        error_log('[GET_PLATS] JSON encode failed: ' . json_last_error_msg());
        echo json_encode([
            'success' => false,
            'message' => 'Erreur d\'encodage JSON'
        ]);
    } else {
        error_log('[GET_PLATS] JSON response length: ' . strlen($response));
        echo $response;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>

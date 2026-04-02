<?php
session_start();
require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfPlatModel.php';

header('Content-Type: application/json');

try {
    error_log('[GET_PLATS] Starting...');
    $pdo = connexionPDO();
    error_log('[GET_PLATS] DB connected');
    $platModel = new TfPlatModel($pdo);

    $plats = $platModel->getAllPlats();
    error_log('[GET_PLATS] Found ' . count($plats) . ' plats');

    // Nettoyer les données pour éviter les problèmes d'encodage JSON
    foreach ($plats as &$plat) {
        foreach ($plat as $key => $value) {
            if (is_string($value)) {
                if ($key === 'plat_image' && !empty($value)) {
                    // Encoder l'image BLOB en base64 pour l'affichage
                    $plat[$key] = 'data:image/jpeg;base64,' . base64_encode($value);
                } else {
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
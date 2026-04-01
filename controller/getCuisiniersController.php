<?php

header('Content-Type: application/json');

error_log('[GET_CUISINIERS] Début du traitement...');

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

try {
    $pdo = connexionPDO();
    error_log('[GET_CUISINIERS] Connexion à la base de données établie');
    
    $model = new TfUserModel($pdo);
    
    // Récupérer tous les cuisiniers
    $cuisiniers = $model->getCuisiniers();
    
    error_log('[GET_CUISINIERS] ' . count($cuisiniers) . ' cuisiniers récupérés');
    
    if (empty($cuisiniers)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Aucun cuisinier trouvé'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $cuisiniers,
            'count' => count($cuisiniers)
        ]);
    }
} catch (Exception $e) {
    error_log('[GET_CUISINIERS] ✗ Exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>

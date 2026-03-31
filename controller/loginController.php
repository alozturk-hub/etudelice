<?php

session_start();

error_log('[LOGIN] Début du traitement de la connexion');

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('[LOGIN] Requête POST reçue');
    error_log('[LOGIN] Données POST: ' . json_encode($_POST));

    try {
        $pdo = connexionPDO();
        error_log('[LOGIN] Connexion à la base de données établie');
        
        $model = new TfUserModel($pdo);

        $email = htmlspecialchars(trim($_POST['email']));
        $password = $_POST['password'];

        error_log('[LOGIN] Tentative de connexion pour: ' . $email);

        // Vérification login
        $user = $model->login($email, $password);

        if ($user) {
            error_log('[LOGIN] Authentification réussie pour l\'utilisateur: ' . $user['user_id']);
            
            // Création de la session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_nom'] = $user['user_nom'];
            $_SESSION['user_prenom'] = $user['user_prenom'];
            $_SESSION['user_mail'] = $user['user_mail'];
            $_SESSION['user_role'] = $user['role_id'];

            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie'
            ]);
        } else {
            error_log('[LOGIN] Authentification échouée - identifiants invalides');
            echo json_encode([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ]);
        }
    } catch (Exception $e) {
        error_log('[LOGIN] Erreur: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}

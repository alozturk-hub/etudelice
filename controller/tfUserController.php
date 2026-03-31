<?php

session_start();

error_log('[INSCRIPTION] Début du traitement de l\'inscription');

require_once '../modele/etudeliceDataBase.php';
require_once '../modele/tfUserModel.php';

error_log('[INSCRIPTION] Fichiers inclus avec succès');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('[INSCRIPTION] Requête POST reçue');
    error_log('[INSCRIPTION] Données POST: ' . json_encode($_POST));

    try {
        $pdo = connexionPDO();
        error_log('[INSCRIPTION] Connexion à la base de données établie');
        
        $model = new TfUserModel($pdo);

        $nom = htmlspecialchars(trim($_POST['nom']));
        $prenom = htmlspecialchars(trim($_POST['prenom']));
        $email = htmlspecialchars(trim($_POST['mail']));
        $password = $_POST['password']; 
        $telephone = htmlspecialchars(trim($_POST['telephone']));
        $type = $_POST['type']; // on vérifiera juste après que ce soit 'usager' ou 'cuisinier'

        error_log('[INSCRIPTION] Données nettoyées - Email: ' . $email . ', Type: ' . $type);

        // Déterminer le rôle
        if ($type === 'cuisinier') {
            $role_id = 2;
            $specialite = isset($_POST['specialite']) ? htmlspecialchars(trim($_POST['specialite'])) : null;
        } else {
            $role_id = 1;
            $specialite = null;
        }

        error_log('[INSCRIPTION] Création utilisateur en cours...');
        
        // Création utilisateur
        $success = $model->createUser(
            $nom,
            $prenom,
            $email,
            $password,
            $role_id,
            $telephone,
            $specialite
        );

        if ($success) {
            // Récupérer l'utilisateur juste créé
            $user = $model->getUserByEmail($email);
            if ($user) {
                // Créer la session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_nom'] = $user['user_nom'];
                $_SESSION['user_prenom'] = $user['user_prenom'];
                $_SESSION['user_mail'] = $user['user_mail'];
                $_SESSION['user_role'] = $user['role_id'];
                
                error_log('[INSCRIPTION] ✓ Session créée pour: ' . $user['user_nom'] . ' ' . $user['user_prenom']);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Utilisateur créé avec succès !',
                    'redirect' => '../index.html'
                ]);
            } else {
                error_log('[INSCRIPTION] ✗ Impossible de récupérer l\'utilisateur créé');
                header('Content-Type: application/json', true, 500);
                echo json_encode(['success' => false, 'message' => 'Erreur: utilisateur créé mais non trouvable']);
            }
        } else {
            error_log('[INSCRIPTION] ✗ Erreur lors de la création de l\'utilisateur');
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur.']);
        }
    } catch (Exception $e) {
        error_log('[INSCRIPTION] ✗ Exception: ' . $e->getMessage());
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
} else {
    error_log('[INSCRIPTION] Méthode non-POST reçue: ' . $_SERVER['REQUEST_METHOD']);
    header('Content-Type: application/json', true, 405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

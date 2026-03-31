<?php

session_start();

header('Content-Type: application/json');

error_log('[LOGOUT] Déconnexion de l\'utilisateur: ' . ($_SESSION['user_mail'] ?? 'unknown'));

// Détruire la session
session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Déconnexion réussie'
]);

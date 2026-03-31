<?php

session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'user_nom' => $_SESSION['user_nom'],
        'user_prenom' => $_SESSION['user_prenom'],
        'user_mail' => $_SESSION['user_mail'],
        'user_role' => $_SESSION['user_role']
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}

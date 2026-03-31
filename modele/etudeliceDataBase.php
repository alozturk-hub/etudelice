<?php

//Fonction permettant de se connecter à la BDD
function connexionPDO(){
  error_log('[DB] Tentative de connexion à la base de données...');
  
  // DSN avec timeout de connexion de 5 secondes
  /* $dsn = 'mysql:host=q85sv.myd.infomaniak.com;dbname=q85sv_etudelice;port=3306;connect_timeout=5';
	$utilisateur = 'q85sv_Groupe4';
	$mot_de_passe = 'G@teaux!123'; */

	$dsn = 'mysql:host=;dbname=q85sv_etudelice;port=3306;connect_timeout=5';
	$utilisateur = 'etudelice';
	$mot_de_passe = 'etudelice'; 
	
	try {
    error_log('[DB] Création de l\'instance PDO...');
    // Créer une instance de la classe PDO
    	$connexion = new PDO($dsn, $utilisateur, $mot_de_passe);

    // Configurer PDO pour signaler les erreurs
    	$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  		$connexion->setAttribute(PDO::ATTR_TIMEOUT, 5);

    error_log('[DB] ✓ Connexion établie avec succès');
    	return $connexion;
	}
	 catch (PDOException $e) {
    // Gérer les erreurs de connexion
    	error_log('[DB] ✗ Erreur de connexion : ' . $e->getMessage());
    	die('Erreur de connexion à la base de données : ' . $e->getMessage());
	}
}
?>
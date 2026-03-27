<?php

//Fonction permettant de se connecter à la BDD
function connexionPDO(){
  $dsn = 'mysql:host=q85sv.myd.infomaniak.com;dbname=q85sv_etudelice;port=3306';
	$utilisateur = 'q85sv_Groupe4';
	$mot_de_passe = 'G@teaux!123';
	try {
    // Créer une instance de la classe PDO
    	$connexion = new PDO($dsn, $utilisateur, $mot_de_passe);

    // Configurer PDO pour signaler les erreurs
    	$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  		$connexion->setAttribute(PDO::ATTR_TIMEOUT, 5);

    
    	return $connexion;
	}
	 catch (PDOException $e) {
    // Gérer les erreurs de connexion
    	echo 'Erreur de connexion : ' . $e->getMessage();
	}
}
?>
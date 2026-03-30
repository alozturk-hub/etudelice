<?php

//Controller, en MVC, le controller s'occupe de rederiger sur la bonne page selon l'action de l'user
function controleurPrincipal($action){
    $lesActions = array();
    $lesActions["defaut"] = "index.php";
   
    
    if (array_key_exists ( $action , $lesActions )){
        return $lesActions[$action];
    }
    else{
        return $lesActions["defaut"];
    }

}

?>
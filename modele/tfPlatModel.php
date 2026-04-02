<?php

class TfPlatModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer tous les plats
     */
    public function getAllPlats()
    {
        $sql = "SELECT plat_id, plat_libelle, plat_description, plat_prix, plat_image FROM tf_plat ORDER BY plat_libelle";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un plat par ID
     */
    public function getPlatById($id)
    {
        $sql = "SELECT * FROM tf_plat WHERE plat_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
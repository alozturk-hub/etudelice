<?php

class TaReservationModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer par ID client
     */
    public function getReservationsByUserId($id)
    {
        $sql = "SELECT res.*, st.status_libelle as reservation_status_text 
                FROM ta_reservation res 
                LEFT JOIN tf_status st ON res.reservation_statut = st.status_id 
                WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // ✅ CORRECTION ICI
    }

    /**
     * Récupérer par ID cuisinier
     */
    public function getReservationByUserCuisinierId($id)
    {
        $sql = "SELECT res.*, st.status_libelle as reservation_status_text 
                FROM ta_reservation res 
                LEFT JOIN tf_status st ON res.reservation_statut = st.status_id 
                WHERE user_id_1 = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // ✅ aussi ici (cohérent)
    }
}
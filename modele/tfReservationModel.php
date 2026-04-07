<?php

class TfReservationModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Créer une nouvelle réservation
     * @param int $userId - ID de l'utilisateur qui commande
     * @param int $cuisinierId - ID du cuisinier
     * @param array $plats - Tableau avec plat_id et quantité (ou quantité par défaut 1)
     * @return int - ID de la réservation créée ou false si erreur
     */
    public function createReservation($userId, $cuisinierId, $plats)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Créer la réservation
            $sql = "INSERT INTO ta_reservation (
                        reservation_date,
                        reservation_statut,
                        user_id,
                        user_id_1
                    ) VALUES (
                        NOW(),
                        1,
                        :user_id,
                        :cuisinier_id
                    )";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':cuisinier_id' => $cuisinierId
            ]);

            if (!$result) {
                throw new Exception('Erreur lors de la création de la réservation');
            }

            // Récupérer l'ID de la réservation créée
            $reservationId = $this->pdo->lastInsertId();

            // 2. Ajouter les plats à la réservation
            foreach ($plats as $plat) {
                $platId = $plat['id'];
                $quantite = isset($plat['quantite']) ? $plat['quantite'] : 1;

                $sqlPlat = "INSERT INTO ta_plat_reservation (
                                plat_id,
                                reservation_id,
                                plat_reservation_quantite
                            ) VALUES (
                                :plat_id,
                                :reservation_id,
                                :quantite
                            )";

                $stmtPlat = $this->pdo->prepare($sqlPlat);
                $resultPlat = $stmtPlat->execute([
                    ':plat_id' => $platId,
                    ':reservation_id' => $reservationId,
                    ':quantite' => $quantite
                ]);

                if (!$resultPlat) {
                    throw new Exception('Erreur lors de l\'ajout des plats');
                }
            }

            $this->pdo->commit();
            
            error_log('[RESERVATION] Réservation créée avec succès - ID: ' . $reservationId);
            
            return $reservationId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('[RESERVATION] Erreur: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer une réservation par ID
     */
    public function getReservationById($reservationId)
    {
        $sql = "SELECT * FROM ta_reservation WHERE reservation_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $reservationId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les plats d'une réservation
     */
    public function getReservationPlats($reservationId)
    {
        $sql = "SELECT 
                    tp.plat_id,
                    tp.plat_libelle,
                    tp.plat_description,
                    tp.plat_prix,
                    tpr.plat_reservation_quantite
                FROM ta_plat_reservation tpr
                JOIN tf_plat tp ON tpr.plat_id = tp.plat_id
                WHERE tpr.reservation_id = :reservation_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':reservation_id' => $reservationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer toutes les réservations d'un utilisateur
     */
    public function getUserReservations($userId)
    {
        $sql = "SELECT 
                    res.*,
                    cu.user_prenom as cuisinier_prenom,
                    cu.user_nom as cuisinier_nom,
                    cu.user_mail as cuisinier_mail,
                    cl.user_prenom as client_prenom,
                    cl.user_nom as client_nom,
                    cl.user_mail as client_mail
                FROM ta_reservation res
                JOIN tf_user cu ON res.user_id_1 = cu.user_id
                JOIN tf_user cl ON res.user_id = cl.user_id
                WHERE res.user_id = :user_id OR res.user_id_1 = :user_id
                ORDER BY res.reservation_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les réservations d'un cuisinier
     */
    public function getCuisinierReservations($cuisinierId)
    {
        $sql = "SELECT 
                    res.*,
                    cu.user_prenom as cuisinier_prenom,
                    cu.user_nom as cuisinier_nom,
                    cu.user_mail as cuisinier_mail,
                    cl.user_prenom as client_prenom,
                    cl.user_nom as client_nom,
                    cl.user_mail as client_mail
                FROM ta_reservation res
                JOIN tf_user cu ON res.user_id_1 = cu.user_id
                JOIN tf_user cl ON res.user_id = cl.user_id
                WHERE res.user_id_1 = :cuisinier_id
                ORDER BY res.reservation_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cuisinier_id' => $cuisinierId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mettre à jour le statut d'une réservation
     */
    public function updateReservationStatus($reservationId, $status)
    {
        $sql = "UPDATE ta_reservation SET reservation_statut = :status WHERE reservation_id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':status' => $status,
            ':id' => $reservationId
        ]);
    }
}

?>

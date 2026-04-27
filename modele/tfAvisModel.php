<?php

class TfAvisModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCuisinierRatingSummary($cuisinierId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                ROUND(AVG(avis_note), 1) AS note_moyenne,
                COUNT(*) AS note_count
             FROM ta_avis_cuisinier
             WHERE cuisinier_user_id = :cuisinier_id"
        );
        $stmt->execute([':cuisinier_id' => $cuisinierId]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'note_moyenne' => isset($summary['note_moyenne']) ? (float) $summary['note_moyenne'] : 0.0,
            'note_count' => (int) ($summary['note_count'] ?? 0),
        ];
    }

    public function getRecentReviewsForCuisinier($cuisinierId, $limit = 5)
    {
        $limit = max(1, (int) $limit);
        $sql = "SELECT
                    avis.avis_id,
                    avis.avis_note,
                    avis.avis_commentaire,
                    avis.created_at,
                    client.user_prenom AS client_prenom,
                    client.user_nom AS client_nom
                FROM ta_avis_cuisinier avis
                JOIN tf_user client ON client.user_id = avis.client_user_id
                WHERE avis.cuisinier_user_id = :cuisinier_id
                ORDER BY avis.updated_at DESC, avis.created_at DESC
                LIMIT $limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cuisinier_id' => $cuisinierId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewByClientAndCuisinier($clientId, $cuisinierId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM ta_avis_cuisinier
             WHERE client_user_id = :client_id
               AND cuisinier_user_id = :cuisinier_id"
        );
        $stmt->execute([
            ':client_id' => $clientId,
            ':cuisinier_id' => $cuisinierId,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function canClientReviewCuisinier($clientId, $cuisinierId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT res.reservation_id
             FROM ta_reservation res
             WHERE res.user_id = :client_id
               AND res.user_id_1 = :cuisinier_id
               AND res.reservation_statut = 3
             ORDER BY res.reservation_date DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':client_id' => $clientId,
            ':cuisinier_id' => $cuisinierId,
        ]);

        $reservationId = $stmt->fetchColumn();

        return [
            'allowed' => $reservationId !== false,
            'reservation_id' => $reservationId !== false ? (int) $reservationId : null,
        ];
    }

    public function saveReview($clientId, $cuisinierId, $note, $commentaire = '')
    {
        $eligibility = $this->canClientReviewCuisinier($clientId, $cuisinierId);
        if (!$eligibility['allowed']) {
            throw new Exception('Vous devez avoir une commande complétée chez ce cuisinier pour laisser un avis.');
        }

        $note = (int) $note;
        if ($note < 1 || $note > 5) {
            throw new Exception('La note doit être comprise entre 1 et 5.');
        }

        $commentaire = trim((string) $commentaire);
        if (mb_strlen($commentaire) > 1000) {
            throw new Exception('Le commentaire est trop long.');
        }

        $existing = $this->getReviewByClientAndCuisinier($clientId, $cuisinierId);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE ta_avis_cuisinier
                 SET avis_note = :note,
                     avis_commentaire = :commentaire,
                     reservation_id = :reservation_id,
                     updated_at = NOW()
                 WHERE avis_id = :avis_id"
            );
            $stmt->execute([
                ':note' => $note,
                ':commentaire' => $commentaire !== '' ? $commentaire : null,
                ':reservation_id' => $eligibility['reservation_id'],
                ':avis_id' => $existing['avis_id'],
            ]);

            return (int) $existing['avis_id'];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO ta_avis_cuisinier (
                client_user_id,
                cuisinier_user_id,
                reservation_id,
                avis_note,
                avis_commentaire,
                created_at,
                updated_at
            ) VALUES (
                :client_id,
                :cuisinier_id,
                :reservation_id,
                :note,
                :commentaire,
                NOW(),
                NOW()
            )"
        );
        $stmt->execute([
            ':client_id' => $clientId,
            ':cuisinier_id' => $cuisinierId,
            ':reservation_id' => $eligibility['reservation_id'],
            ':note' => $note,
            ':commentaire' => $commentaire !== '' ? $commentaire : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}

?>

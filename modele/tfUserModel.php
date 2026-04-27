<?php

class TfUserModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Création d’un utilisateur
     */
    public function createUser($nom, $prenom, $mail, $password, $role_id, $telephone, $specialite = null)
    {
        $sql = "INSERT INTO tf_user (
                    user_nom,
                    user_prenom,
                    user_mail,
                    user_password,
                    role_id,
                    user_telephone,
                    user_specialite
                ) VALUES (
                    :nom,
                    :prenom,
                    :mail,
                    :password,
                    :role_id,
                    :telephone,
                    :specialite
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':mail' => $mail,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':role_id' => $role_id,
            ':telephone' => $telephone,
            ':specialite' => $specialite
        ]);
    }

    /**
     * Récupérer un utilisateur par email
     */
    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM tf_user WHERE user_mail = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérification login
     */
    public function login($email, $password)
    {
        $user = $this->getUserByEmail($email);

        if ($user && password_verify($password, $user['user_password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Récupérer par ID
     */
    public function getUserById($id)
    {
        $sql = "SELECT * FROM tf_user WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePasswordById($id, $password)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tf_user
             SET user_password = :password
             WHERE user_id = :user_id"
        );

        return $stmt->execute([
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':user_id' => $id
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($id, $nom, $prenom, $mail, $telephone, $specialite = null, $password = null)
    {
        $fields = [
            'user_nom' => $nom,
            'user_prenom' => $prenom,
            'user_mail' => $mail,
            'user_telephone' => $telephone,
            'user_specialite' => $specialite,
            'user_id' => $id
        ];

        $sql = "UPDATE tf_user SET
                    user_nom = :user_nom,
                    user_prenom = :user_prenom,
                    user_mail = :user_mail,
                    user_telephone = :user_telephone,
                    user_specialite = :user_specialite";

        if (!empty($password)) {
            $sql .= ", user_password = :user_password";
            $fields['user_password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($fields);
    }

    /**
     * Récupérer tous les cuisiniers (role_id = 2)
     */
    public function getCuisiniers()
    {
        $sql = "SELECT user_id, user_nom, user_prenom, user_specialite FROM tf_user WHERE role_id = 2 ORDER BY user_prenom, user_nom";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPasswordResetToken($userId, $tokenHash, $expiresAt)
    {
        $this->clearPasswordResetTokensForUser($userId);

        $stmt = $this->pdo->prepare(
            "INSERT INTO tf_password_reset (
                user_id,
                reset_token_hash,
                expires_at,
                created_at
            ) VALUES (
                :user_id,
                :token_hash,
                :expires_at,
                NOW()
            )"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt
        ]);
    }

    public function getValidPasswordResetByToken($token)
    {
        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            "SELECT pr.*, u.user_mail, u.user_prenom, u.user_nom
             FROM tf_password_reset pr
             JOIN tf_user u ON u.user_id = pr.user_id
             WHERE pr.reset_token_hash = :token_hash
               AND pr.used_at IS NULL
               AND pr.expires_at >= NOW()
             LIMIT 1"
        );
        $stmt->execute([':token_hash' => $tokenHash]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function markPasswordResetAsUsed($resetId)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tf_password_reset
             SET used_at = NOW()
             WHERE reset_id = :reset_id"
        );

        return $stmt->execute([':reset_id' => $resetId]);
    }

    public function clearPasswordResetTokensForUser($userId)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM tf_password_reset WHERE user_id = :user_id"
        );

        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Supprimer complètement un compte utilisateur et ses données liées.
     */
    public function deleteUserAccount($userId)
    {
        $this->pdo->beginTransaction();

        try {
            $reservationIds = [];
            $reservationStmt = $this->pdo->prepare(
                "SELECT reservation_id
                 FROM ta_reservation
                 WHERE user_id = :user_id OR user_id_1 = :user_id"
            );
            $reservationStmt->execute([':user_id' => $userId]);
            $reservationIds = $reservationStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($reservationIds)) {
                $placeholders = implode(',', array_fill(0, count($reservationIds), '?'));

                $deleteReservationPlats = $this->pdo->prepare(
                    "DELETE FROM ta_plat_reservation
                     WHERE reservation_id IN ($placeholders)"
                );
                $deleteReservationPlats->execute($reservationIds);

                $deleteReservations = $this->pdo->prepare(
                    "DELETE FROM ta_reservation
                     WHERE reservation_id IN ($placeholders)"
                );
                $deleteReservations->execute($reservationIds);
            }

            $deleteUserIngredients = $this->pdo->prepare(
                "DELETE FROM ta_user_ingredient WHERE user_id = :user_id"
            );
            $deleteUserIngredients->execute([':user_id' => $userId]);

            $deleteOwnPlats = $this->pdo->prepare(
                "DELETE FROM tf_cuisinier_plat WHERE user_id = :user_id"
            );
            $deleteOwnPlats->execute([':user_id' => $userId]);

            $deleteUser = $this->pdo->prepare(
                "DELETE FROM tf_user WHERE user_id = :user_id"
            );
            $deleteUser->execute([':user_id' => $userId]);

            $this->pdo->commit();

            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

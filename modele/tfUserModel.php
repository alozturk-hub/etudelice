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
}

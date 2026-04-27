<?php

class TfCuisinierPlatModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPlatsForCuisinier($cuisinierId, $clientId = null)
    {
        $sql = "SELECT
                    cp.plat_id,
                    cp.user_id,
                    cp.plat_libelle,
                    cp.plat_description,
                    cp.plat_prix,
                    cp.plat_image,
                    cp.plat_disponible
                FROM tf_cuisinier_plat cp
                WHERE cp.user_id = :cuisinier_id
                  AND cp.plat_disponible = 1
                ORDER BY cp.plat_libelle";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cuisinier_id' => $cuisinierId]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $clientIngredients = $clientId ? $this->getUserIngredientLabels($clientId) : [];

        foreach ($plats as &$plat) {
            $plat['ingredients'] = $this->getPlatIngredients($plat['plat_id']);
            $pricing = $this->buildPricing($plat['plat_prix'], $plat['ingredients'], $clientIngredients);
            $plat = array_merge($plat, $pricing);
        }

        return $plats;
    }

    public function getPlatById($platId)
    {
        $sql = "SELECT
                    cp.plat_id,
                    cp.user_id,
                    cp.plat_libelle,
                    cp.plat_description,
                    cp.plat_prix,
                    cp.plat_image,
                    cp.plat_disponible
                FROM tf_cuisinier_plat cp
                WHERE cp.plat_id = :plat_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':plat_id' => $platId]);
        $plat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plat) {
            return null;
        }

        $plat['ingredients'] = $this->getPlatIngredients($platId);

        return $plat;
    }

    public function getOwnedPlatById($platId, $cuisinierId)
    {
        $sql = "SELECT *
                FROM tf_cuisinier_plat
                WHERE plat_id = :plat_id
                  AND user_id = :cuisinier_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':plat_id' => $platId,
            ':cuisinier_id' => $cuisinierId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPlatsForProfile($cuisinierId)
    {
        $sql = "SELECT
                    cp.plat_id,
                    cp.plat_libelle,
                    cp.plat_description,
                    cp.plat_prix,
                    cp.plat_image,
                    cp.plat_disponible
                FROM tf_cuisinier_plat cp
                WHERE cp.user_id = :cuisinier_id
                ORDER BY cp.created_at DESC, cp.plat_libelle";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cuisinier_id' => $cuisinierId]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($plats as &$plat) {
            $plat['ingredients'] = $this->getPlatIngredients($plat['plat_id']);
        }

        return $plats;
    }

    public function createPlatForCuisinier($cuisinierId, array $data)
    {
        $ingredients = $this->normalizeIngredientInput($data['ingredients'] ?? []);
        $imageBinary = $this->dataUrlToBinary($data['imageData'] ?? null);

        $this->pdo->beginTransaction();

        try {
            $sql = "INSERT INTO tf_cuisinier_plat (
                        user_id,
                        plat_libelle,
                        plat_description,
                        plat_prix,
                        plat_image,
                        plat_disponible
                    ) VALUES (
                        :user_id,
                        :libelle,
                        :description,
                        :prix,
                        :image,
                        1
                    )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $cuisinierId, PDO::PARAM_INT);
            $stmt->bindValue(':libelle', trim($data['libelle']));
            $stmt->bindValue(':description', trim($data['description']));
            $stmt->bindValue(':prix', round((float) $data['prix'], 2));
            $stmt->bindValue(':image', $imageBinary, $imageBinary === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
            $stmt->execute();

            $platId = (int) $this->pdo->lastInsertId();
            $this->replacePlatIngredients($platId, $ingredients);

            $this->pdo->commit();

            return $platId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getUserIngredients($userId)
    {
        $sql = "SELECT
                    ui.ingredient_id,
                    ing.ingredient_libelle,
                    ui.user_ingredient_quantite
                FROM ta_user_ingredient ui
                JOIN tf_ingredient ing ON ing.ingredient_id = ui.ingredient_id
                WHERE ui.user_id = :user_id
                ORDER BY ing.ingredient_libelle";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function replaceUserIngredients($userId, array $ingredients)
    {
        $normalized = $this->normalizeIngredientInput($ingredients, true);

        $this->pdo->beginTransaction();

        try {
            $deleteStmt = $this->pdo->prepare("DELETE FROM ta_user_ingredient WHERE user_id = :user_id");
            $deleteStmt->execute([':user_id' => $userId]);

            if (!empty($normalized)) {
                $ingredientIds = $this->findOrCreateIngredients($normalized);
                $insertStmt = $this->pdo->prepare(
                    "INSERT INTO ta_user_ingredient (
                        user_id,
                        ingredient_id,
                        user_ingredient_quantite,
                        updated_at
                    ) VALUES (
                        :user_id,
                        :ingredient_id,
                        :quantite,
                        NOW()
                    )"
                );

                foreach ($normalized as $ingredient) {
                    $labelKey = $this->normalizeLabel($ingredient['libelle']);
                    $insertStmt->execute([
                        ':user_id' => $userId,
                        ':ingredient_id' => $ingredientIds[$labelKey],
                        ':quantite' => $ingredient['quantite']
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function buildReservationPricing($platId, $clientId)
    {
        $plat = $this->getPlatById($platId);

        if (!$plat) {
            throw new Exception('Plat introuvable.');
        }

        $clientIngredients = $clientId ? $this->getUserIngredientLabels($clientId) : [];
        $pricing = $this->buildPricing($plat['plat_prix'], $plat['ingredients'], $clientIngredients);

        return array_merge($plat, $pricing);
    }

    private function getPlatIngredients($platId)
    {
        $sql = "SELECT
                    ing.ingredient_id,
                    ing.ingredient_libelle
                FROM ta_plat_ingredient pi
                JOIN tf_ingredient ing ON ing.ingredient_id = pi.ingredient_id
                WHERE pi.plat_id = :plat_id
                ORDER BY ing.ingredient_libelle";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':plat_id' => $platId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getUserIngredientLabels($userId)
    {
        $ingredients = $this->getUserIngredients($userId);
        $labels = [];

        foreach ($ingredients as $ingredient) {
            $labels[$this->normalizeLabel($ingredient['ingredient_libelle'])] = true;
        }

        return $labels;
    }

    private function buildPricing($basePrice, array $platIngredients, array $clientIngredients)
    {
        $matchedIngredients = [];

        foreach ($platIngredients as $ingredient) {
            $normalized = $this->normalizeLabel($ingredient['ingredient_libelle']);
            if (isset($clientIngredients[$normalized])) {
                $matchedIngredients[] = $ingredient['ingredient_libelle'];
            }
        }

        $discountRate = min(count($matchedIngredients) * 0.10, 0.50);
        $basePrice = round((float) $basePrice, 2);
        $discountedPrice = round($basePrice * (1 - $discountRate), 2);

        return [
            'plat_prix_original' => $basePrice,
            'plat_prix_reduit' => $discountedPrice,
            'reduction_taux' => $discountRate,
            'ingredients_disponibles' => $matchedIngredients
        ];
    }

    private function replacePlatIngredients($platId, array $ingredients)
    {
        $deleteStmt = $this->pdo->prepare("DELETE FROM ta_plat_ingredient WHERE plat_id = :plat_id");
        $deleteStmt->execute([':plat_id' => $platId]);

        if (empty($ingredients)) {
            return;
        }

        $ingredientIds = $this->findOrCreateIngredients($ingredients);
        $insertStmt = $this->pdo->prepare(
            "INSERT INTO ta_plat_ingredient (plat_id, ingredient_id)
             VALUES (:plat_id, :ingredient_id)"
        );

        foreach ($ingredients as $ingredient) {
            $labelKey = $this->normalizeLabel($ingredient['libelle']);
            $insertStmt->execute([
                ':plat_id' => $platId,
                ':ingredient_id' => $ingredientIds[$labelKey]
            ]);
        }
    }

    private function findOrCreateIngredients(array $ingredients)
    {
        $ids = [];
        $selectStmt = $this->pdo->prepare(
            "SELECT ingredient_id
             FROM tf_ingredient
             WHERE LOWER(ingredient_libelle) = :label"
        );
        $insertStmt = $this->pdo->prepare(
            "INSERT INTO tf_ingredient (ingredient_libelle)
             VALUES (:label)"
        );

        foreach ($ingredients as $ingredient) {
            $rawLabel = trim($ingredient['libelle']);
            $normalized = $this->normalizeLabel($rawLabel);

            if ($normalized === '' || isset($ids[$normalized])) {
                continue;
            }

            $selectStmt->execute([':label' => $normalized]);
            $existingId = $selectStmt->fetchColumn();

            if ($existingId) {
                $ids[$normalized] = (int) $existingId;
                continue;
            }

            $insertStmt->execute([':label' => $rawLabel]);
            $ids[$normalized] = (int) $this->pdo->lastInsertId();
        }

        return $ids;
    }

    private function normalizeIngredientInput($ingredients, $withQuantities = false)
    {
        $normalized = [];

        if (is_string($ingredients)) {
            $ingredients = preg_split('/[\r\n,;]+/', $ingredients);
        }

        foreach ((array) $ingredients as $ingredient) {
            if (is_array($ingredient)) {
                $libelle = trim((string) ($ingredient['libelle'] ?? $ingredient['ingredient_libelle'] ?? ''));
                $quantite = trim((string) ($ingredient['quantite'] ?? $ingredient['user_ingredient_quantite'] ?? ''));
            } else {
                $libelle = trim((string) $ingredient);
                $quantite = '';
            }

            if ($libelle === '') {
                continue;
            }

            $key = $this->normalizeLabel($libelle);
            if (isset($normalized[$key])) {
                if ($withQuantities && $quantite !== '') {
                    $normalized[$key]['quantite'] = $quantite;
                }
                continue;
            }

            $normalized[$key] = [
                'libelle' => $libelle,
                'quantite' => $withQuantities ? $quantite : null
            ];
        }

        return array_values($normalized);
    }

    private function normalizeLabel($label)
    {
        return mb_strtolower(trim((string) $label), 'UTF-8');
    }

    private function dataUrlToBinary($imageData)
    {
        if (empty($imageData) || !is_string($imageData)) {
            return null;
        }

        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.+)$/', $imageData, $matches) !== 1) {
            throw new Exception('Format d\'image invalide.');
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false) {
            throw new Exception('Image base64 invalide.');
        }

        return $binary;
    }
}

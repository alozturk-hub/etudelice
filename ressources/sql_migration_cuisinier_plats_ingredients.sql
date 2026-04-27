START TRANSACTION;

CREATE TABLE `tf_cuisinier_plat` (
  `plat_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `plat_libelle` varchar(50) NOT NULL,
  `plat_description` varchar(255) NOT NULL,
  `plat_prix` decimal(15,2) NOT NULL,
  `plat_image` longblob,
  `plat_disponible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`plat_id`),
  KEY `fk_cuisinier_plat_user` (`user_id`),
  CONSTRAINT `fk_cuisinier_plat_user`
    FOREIGN KEY (`user_id`) REFERENCES `tf_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tf_cuisinier_plat` (
  `plat_id`,
  `user_id`,
  `plat_libelle`,
  `plat_description`,
  `plat_prix`,
  `plat_image`,
  `plat_disponible`
)
SELECT
  `plat_id`,
  NULL,
  `plat_libelle`,
  `plat_description`,
  `plat_prix`,
  `plat_image`,
  1
FROM `tf_plat`
WHERE NOT EXISTS (
  SELECT 1
  FROM `tf_cuisinier_plat` cp
  WHERE cp.`plat_id` = `tf_plat`.`plat_id`
);

ALTER TABLE `ta_plat_reservation`
  ADD COLUMN `plat_reservation_prix_unitaire` decimal(15,2) DEFAULT NULL AFTER `plat_reservation_quantite`,
  ADD COLUMN `plat_reservation_prix_original` decimal(15,2) DEFAULT NULL AFTER `plat_reservation_prix_unitaire`;

UPDATE `ta_plat_reservation` tpr
JOIN `tf_plat` tp ON tp.`plat_id` = tpr.`plat_id`
SET
  tpr.`plat_reservation_prix_unitaire` = tp.`plat_prix`,
  tpr.`plat_reservation_prix_original` = tp.`plat_prix`
WHERE tpr.`plat_reservation_prix_unitaire` IS NULL
   OR tpr.`plat_reservation_prix_original` IS NULL;

ALTER TABLE `ta_plat_reservation`
  DROP FOREIGN KEY `ta_plat_reservation_ibfk_1`;

ALTER TABLE `ta_plat_reservation`
  ADD CONSTRAINT `fk_ta_plat_reservation_cuisinier_plat`
    FOREIGN KEY (`plat_id`) REFERENCES `tf_cuisinier_plat` (`plat_id`);

CREATE TABLE `tf_ingredient` (
  `ingredient_id` int NOT NULL AUTO_INCREMENT,
  `ingredient_libelle` varchar(100) NOT NULL,
  PRIMARY KEY (`ingredient_id`),
  UNIQUE KEY `uq_ingredient_libelle` (`ingredient_libelle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_plat_ingredient` (
  `plat_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  PRIMARY KEY (`plat_id`, `ingredient_id`),
  KEY `fk_plat_ingredient_ingredient` (`ingredient_id`),
  CONSTRAINT `fk_plat_ingredient_plat`
    FOREIGN KEY (`plat_id`) REFERENCES `tf_cuisinier_plat` (`plat_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plat_ingredient_ingredient`
    FOREIGN KEY (`ingredient_id`) REFERENCES `tf_ingredient` (`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_user_ingredient` (
  `user_id` int NOT NULL,
  `ingredient_id` int NOT NULL,
  `user_ingredient_quantite` varchar(50) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `ingredient_id`),
  KEY `fk_user_ingredient_ingredient` (`ingredient_id`),
  CONSTRAINT `fk_user_ingredient_user`
    FOREIGN KEY (`user_id`) REFERENCES `tf_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_ingredient_ingredient`
    FOREIGN KEY (`ingredient_id`) REFERENCES `tf_ingredient` (`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ta_avis_cuisinier` (
  `avis_id` int NOT NULL AUTO_INCREMENT,
  `client_user_id` int NOT NULL,
  `cuisinier_user_id` int NOT NULL,
  `reservation_id` int DEFAULT NULL,
  `avis_note` tinyint NOT NULL,
  `avis_commentaire` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`avis_id`),
  UNIQUE KEY `uq_avis_client_cuisinier` (`client_user_id`, `cuisinier_user_id`),
  KEY `fk_avis_cuisinier_user` (`cuisinier_user_id`),
  KEY `fk_avis_reservation` (`reservation_id`),
  CONSTRAINT `fk_avis_client_user`
    FOREIGN KEY (`client_user_id`) REFERENCES `tf_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_cuisinier_user`
    FOREIGN KEY (`cuisinier_user_id`) REFERENCES `tf_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_reservation`
    FOREIGN KEY (`reservation_id`) REFERENCES `ta_reservation` (`reservation_id`) ON DELETE SET NULL,
  CONSTRAINT `chk_avis_note`
    CHECK (`avis_note` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tf_password_reset` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reset_token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `uq_reset_token_hash` (`reset_token_hash`),
  KEY `fk_password_reset_user` (`user_id`),
  CONSTRAINT `fk_password_reset_user`
    FOREIGN KEY (`user_id`) REFERENCES `tf_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

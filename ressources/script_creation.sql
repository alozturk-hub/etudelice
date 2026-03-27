CREATE TABLE tf_plat(
   plat_id COUNTER,
   plat_libelle VARCHAR(50) NOT NULL,
   plat_description VARCHAR(255) NOT NULL,
   plat_prix DECIMAL(15,2) NOT NULL,
   plat_image VARCHAR(255),
   PRIMARY KEY(plat_id),
   UNIQUE(plat_libelle)
);

CREATE TABLE tf_role(
   role_id COUNTER,
   role_libelle VARCHAR(50) NOT NULL,
   PRIMARY KEY(role_id)
);

CREATE TABLE tf_user(
   user_id COUNTER,
   user_nom VARCHAR(50) NOT NULL,
   user_prenom VARCHAR(50) NOT NULL,
   user_mail VARCHAR(50) NOT NULL,
   user_password VARCHAR(50) NOT NULL,
   role_id INT NOT NULL,
   PRIMARY KEY(user_id),
   FOREIGN KEY(role_id) REFERENCES tf_role(role_id)
);

CREATE TABLE ta_reservation(
   reservation_id COUNTER,
   reservation_date DATETIME NOT NULL,
   reservation_statut INT NOT NULL,
   user_id INT NOT NULL,
   user_id_1 INT NOT NULL,
   PRIMARY KEY(reservation_id),
   FOREIGN KEY(user_id) REFERENCES tf_user(user_id),
   FOREIGN KEY(user_id_1) REFERENCES tf_user(user_id)
);

CREATE TABLE ta_plat_reservation(
   plat_id INT,
   reservation_id INT,
   plat_reservation_quantite INT NOT NULL,
   PRIMARY KEY(plat_id, reservation_id),
   FOREIGN KEY(plat_id) REFERENCES tf_plat(plat_id),
   FOREIGN KEY(reservation_id) REFERENCES ta_reservation(reservation_id)
);
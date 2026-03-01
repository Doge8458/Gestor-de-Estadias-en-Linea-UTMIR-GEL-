CREATE DATABASE IF NOT EXISTS portal_estadias;
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE_portal_estadias;


CREATE TABLE IF NOT EXISTS alumnos
matricula INT(7) UNSIGNED NOT NULL,
curp CHAR(18) NOT NULL,
nombre_completo VARCHAR(100) NOT NULL,

PRIMARY KEY (matricula)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entregas
id_entrega INT AUTO_INCREMENT,
matricula_alumno INT(7) UNSIGNED NOT NULL,

nombre_archivo_subido VARCHAR(255) NOT NULL,
cuatrimestre_subido VARCHAR(20) NOT NULL,
programa_educativo_subido VARCHAR(100) NOT NULL,

link_google_drive VARCHAR(512) NULL,

fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id_entrega),

FOREIGN KEY (matricula_alumno)
REFERENCES alumnos(matricula)
ON DELETE CASCADE
ON UPDATE CASCADE
) ENGINE=InnoDB;

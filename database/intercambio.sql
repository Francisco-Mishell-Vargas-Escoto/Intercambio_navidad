-- =====================================================================
--  Archivo: database/intercambio.sql
--  Proyecto: Plataforma de Intercambio Navideño
--  Descripción: crea la base de datos y TODAS las tablas del sistema.
--  Cómo usarlo: phpMyAdmin > pestaña "Importar" > seleccionar este archivo.
-- =====================================================================

-- Creamos la base de datos con soporte completo de acentos y emojis
CREATE DATABASE IF NOT EXISTS intercambio_navideno
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE intercambio_navideno;

-- Si vuelves a importar el archivo, primero se borran las tablas
-- (en orden inverso a sus dependencias para no romper las llaves foráneas)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS migraciones;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS invitaciones;
DROP TABLE IF EXISTS asignaciones;
DROP TABLE IF EXISTS restricciones;
DROP TABLE IF EXISTS deseos;
DROP TABLE IF EXISTS participantes;
DROP TABLE IF EXISTS intercambios;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USUARIOS: cualquier persona con cuenta (organizador o participante)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120)  NOT NULL,
    email          VARCHAR(150)  NOT NULL,
    password_hash  VARCHAR(255)  NOT NULL,          -- nunca guardamos la contraseña real
    foto           VARCHAR(255)  NULL,              -- nombre del archivo en public/uploads
    creado_en      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. INTERCAMBIOS: cada evento navideño. admin_id = quien lo creó
--    estado: abierto -> cerrado -> sorteado -> revelado
-- ---------------------------------------------------------------------
CREATE TABLE intercambios (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id           INT UNSIGNED  NOT NULL,
    nombre             VARCHAR(120)  NOT NULL,
    descripcion        TEXT          NULL,
    fecha_evento       DATETIME      NOT NULL,
    fecha_limite       DATETIME      NOT NULL,       -- fecha límite para registrarse
    presupuesto_min    DECIMAL(10,2) NOT NULL DEFAULT 0,
    presupuesto_max    DECIMAL(10,2) NOT NULL DEFAULT 0,
    lugar              VARCHAR(200)  NULL,
    reglas             TEXT          NULL,
    codigo_acceso      VARCHAR(12)   NOT NULL,       -- código privado para unirse
    estado             ENUM('abierto','cerrado','sorteado','revelado') NOT NULL DEFAULT 'abierto',
    sorteado_en        DATETIME      NULL,
    revelado_en        DATETIME      NULL,
    creado_en          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_intercambios_codigo (codigo_acceso),
    CONSTRAINT fk_intercambios_admin FOREIGN KEY (admin_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. PARTICIPANTES: personas dentro de un intercambio, con su perfil.
--    NO necesitan cuenta: entran con su nombre y reciben un enlace
--    personal secreto (token) y un código personal corto para volver a entrar. usuario_id solo se llena si la persona
--    tiene cuenta (por ejemplo, el organizador cuando también participa).
-- ---------------------------------------------------------------------
CREATE TABLE participantes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    intercambio_id  INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NULL,           -- opcional: cuenta vinculada
    nombre          VARCHAR(120) NOT NULL,
    email           VARCHAR(150) NULL,
    foto            VARCHAR(255) NULL,           -- archivo en public/uploads
    token           CHAR(64)     NOT NULL,       -- llave del enlace personal /p/{token}
    codigo          CHAR(8)      NOT NULL,       -- código personal corto para entrar (/entrar)
    gustos          TEXT         NULL,   -- gustos personales
    intereses       TEXT         NULL,   -- intereses / hobbies
    no_le_gusta     TEXT         NULL,   -- cosas que NO le gustaría recibir
    preferencias    TEXT         NULL,   -- tallas, colores, etc.
    comentarios     TEXT         NULL,   -- comentarios adicionales
    confirmado      TINYINT(1)   NOT NULL DEFAULT 0,  -- confirmó su participación
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_participante (intercambio_id, usuario_id), -- una cuenta no se une dos veces
    UNIQUE KEY uq_participante_token (token),
    UNIQUE KEY uq_participante_codigo (codigo),
    CONSTRAINT fk_part_intercambio FOREIGN KEY (intercambio_id)
        REFERENCES intercambios(id) ON DELETE CASCADE,
    CONSTRAINT fk_part_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. DESEOS: elementos de la wishlist de cada participante
-- ---------------------------------------------------------------------
CREATE TABLE deseos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participante_id  INT UNSIGNED NOT NULL,
    titulo           VARCHAR(150) NOT NULL,
    descripcion      TEXT         NULL,
    enlace           VARCHAR(500) NULL,   -- enlace al producto
    imagen           VARCHAR(255) NULL,   -- foto de referencia
    prioridad        TINYINT      NOT NULL DEFAULT 2,  -- 1 alta, 2 media, 3 baja
    creado_en        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_deseos_part FOREIGN KEY (participante_id)
        REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. RESTRICCIONES: "dador NO puede regalarle a receptor"
--    bidireccional = 1 significa que tampoco al revés (ej. una pareja)
-- ---------------------------------------------------------------------
CREATE TABLE restricciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    intercambio_id  INT UNSIGNED NOT NULL,
    dador_id        INT UNSIGNED NOT NULL,
    receptor_id     INT UNSIGNED NOT NULL,
    bidireccional   TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_restriccion (intercambio_id, dador_id, receptor_id),
    CONSTRAINT fk_res_intercambio FOREIGN KEY (intercambio_id)
        REFERENCES intercambios(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_dador FOREIGN KEY (dador_id)
        REFERENCES participantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_receptor FOREIGN KEY (receptor_id)
        REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. ASIGNACIONES: resultado del sorteo (¡información privada!)
--    Los dos UNIQUE garantizan desde la base de datos que cada persona
--    da exactamente un regalo y recibe exactamente uno.
-- ---------------------------------------------------------------------
CREATE TABLE asignaciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    intercambio_id  INT UNSIGNED NOT NULL,
    dador_id        INT UNSIGNED NOT NULL,
    receptor_id     INT UNSIGNED NOT NULL,
    visto_en        DATETIME     NULL,    -- cuándo el dador vio su resultado
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asig_dador    (intercambio_id, dador_id),
    UNIQUE KEY uq_asig_receptor (intercambio_id, receptor_id),
    CONSTRAINT chk_no_a_si_mismo CHECK (dador_id <> receptor_id),
    CONSTRAINT fk_asig_intercambio FOREIGN KEY (intercambio_id)
        REFERENCES intercambios(id) ON DELETE CASCADE,
    CONSTRAINT fk_asig_dador FOREIGN KEY (dador_id)
        REFERENCES participantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_asig_receptor FOREIGN KEY (receptor_id)
        REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. INVITACIONES: enlaces únicos enviados por el administrador
-- ---------------------------------------------------------------------
CREATE TABLE invitaciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    intercambio_id  INT UNSIGNED NOT NULL,
    email           VARCHAR(150) NULL,
    token           CHAR(64)     NOT NULL,   -- token aleatorio del enlace
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aceptada_en     DATETIME     NULL,
    UNIQUE KEY uq_invitacion_token (token),
    CONSTRAINT fk_inv_intercambio FOREIGN KEY (intercambio_id)
        REFERENCES intercambios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. NOTIFICACIONES: avisos dentro de la plataforma
--    tipo: registro, confirmacion, sorteo, revelacion, invitacion, general
-- ---------------------------------------------------------------------
CREATE TABLE notificaciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    intercambio_id  INT UNSIGNED NULL,
    tipo            VARCHAR(30)  NOT NULL DEFAULT 'general',
    mensaje         VARCHAR(255) NOT NULL,
    leida           TINYINT(1)   NOT NULL DEFAULT 0,
    creado_en       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_usuario (usuario_id, leida),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_intercambio FOREIGN KEY (intercambio_id)
        REFERENCES intercambios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. MIGRACIONES: cambios de estructura ya aplicados (app/core/Migraciones.php)
-- ---------------------------------------------------------------------
CREATE TABLE migraciones (
    version     INT UNSIGNED PRIMARY KEY,
    aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migraciones (version) VALUES (1), (2);

-- =====================================================================
--  Archivo: database/datos_prueba.sql
--  Datos de ejemplo para probar la plataforma sin capturar todo a mano.
--
--  ¡IMPORTANTE! Importa primero database/intercambio.sql (que deja las tablas
--  vacías) y DESPUÉS este archivo. Borra cualquier dato que tengas.
--
--  Todas las cuentas usan la contraseña:  Navidad2026
--
--   Correo                 | Rol
--   -----------------------|---------------------------------------------------
--   ana@prueba.com         | Organiza "Amigos 2026" (abierto) y participa en "Oficina 2026"
--   luis@prueba.com        | Participa en "Amigos 2026" (pareja de Sofía)
--   sofia@prueba.com       | Participa en "Amigos 2026" (pareja de Luis)
--   carlos@prueba.com      | Participa en "Amigos 2026" y ORGANIZA "Oficina 2026" (ya revelado)
--   mariana@prueba.com     | Participa en ambos
--   diego@prueba.com       | Solo en "Oficina 2026" (sirve para probar accesos prohibidos)
--
--  Paola Gómez participa en "Amigos 2026" SIN cuenta. Puede entrar de dos formas:
--    · Código personal (en "Entrar con mi código"): PAQT-2345
--    · Enlace personal: http://localhost/intercambio_navideno/p/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb
--
--  Código de "Amigos 2026": NAV-AMI-GOS
-- =====================================================================

USE intercambio_navideno;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE notificaciones; TRUNCATE invitaciones; TRUNCATE asignaciones; TRUNCATE restricciones;
TRUNCATE deseos; TRUNCATE participantes; TRUNCATE intercambios; TRUNCATE usuarios;
INSERT IGNORE INTO migraciones (version) VALUES (1), (2);
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Usuarios (contraseña: Navidad2026) ----------
INSERT INTO usuarios (id, nombre, email, password_hash) VALUES
(1, 'Ana López',      'ana@prueba.com',     '$2y$10$kIN/pKjoF4uXh8fnWLNHdOvHFc60R7RhHE3Q/BApQlYhjTASpMb4.'),
(2, 'Luis Pérez',     'luis@prueba.com',    '$2y$10$kIN/pKjoF4uXh8fnWLNHdOvHFc60R7RhHE3Q/BApQlYhjTASpMb4.'),
(3, 'Sofía Ruiz',     'sofia@prueba.com',   '$2y$10$kIN/pKjoF4uXh8fnWLNHdOvHFc60R7RhHE3Q/BApQlYhjTASpMb4.'),
(4, 'Carlos Méndez',  'carlos@prueba.com',  '$2y$10$kIN/pKjoF4uXh8fnWLNHdOvHFc60R7RhHE3Q/BApQlYhjTASpMb4.'),
(5, 'Mariana Torres', 'mariana@prueba.com', '$2y$10$kIN/pKjoF4uXh8fnWLNHdOvHFc60R7RhHE3Q/BApQlYhjTASpMb4.'),
(6, 'Diego Herrera',  'diego@prueba.com',   '$2y$10$kIN/pKjoF4uXh8fnWLNHdOvHFc60R7RhHE3Q/BApQlYhjTASpMb4.');

-- ---------- Intercambios (las fechas se calculan desde HOY) ----------
INSERT INTO intercambios (id, admin_id, nombre, descripcion, fecha_evento, fecha_limite,
                          presupuesto_min, presupuesto_max, lugar, reglas, codigo_acceso, estado, sorteado_en, revelado_en) VALUES
(1, 1, 'Amigos 2026',
 '¡Este año celebramos juntos! Traigan su mejor sonrisa y un regalo envuelto con cariño.',
 DATE_ADD(DATE(NOW()), INTERVAL 40 DAY) + INTERVAL 19 HOUR,
 DATE_ADD(DATE(NOW()), INTERVAL 30 DAY) + INTERVAL 23 HOUR,
 300, 500, 'Casa de Ana, Av. Reforma 123',
 '• El regalo debe ir envuelto y con el nombre de quien lo recibe.\n• Respetar el presupuesto.\n• ¡No revelar a tu amigo secreto antes del día!',
 'NAVAMIGOS', 'abierto', NULL, NULL),
(2, 4, 'Oficina 2026',
 'Intercambio del equipo de la oficina. ¡Habrá ponche!',
 DATE_ADD(DATE(NOW()), INTERVAL 35 DAY) + INTERVAL 14 HOUR,
 DATE_ADD(DATE(NOW()), INTERVAL 5 DAY) + INTERVAL 18 HOUR,
 200, 350, 'Sala de juntas, piso 3', NULL,
 'NAVOFI26X', 'revelado', NOW(), NOW());

-- ---------- Participantes ----------
INSERT INTO participantes (id, intercambio_id, usuario_id, nombre, email, token, codigo, gustos, intereses, no_le_gusta, preferencias, comentarios, confirmado) VALUES
-- Amigos 2026 (ids 1-5 y 10) · Oficina 2026 (ids 6-9)
(1, 1, 1, 'Ana López', 'ana@prueba.com', '9d7f98805b8f9fd1d1ec0fecb63180306d0885e7d207667e6870f34cceb99aaf', 'M9EPXBC5', 'Café de especialidad, plantas', 'Cocinar y leer', 'Perfumes', 'Colores verdes', NULL, 1),
(2, 1, 2, 'Luis Pérez', 'luis@prueba.com', '07632aefdc83f85f9b2a1262624680b7336bee903352ccaea1ffc186ae93e807', 'UDNVB8TG', 'Videojuegos y música', 'Correr', 'Ropa', 'Talla M', NULL, 1),
(3, 1, 3, 'Sofía Ruiz', 'sofia@prueba.com', 'ca4e3d4fc9abb03290041f07e332b7cb25c3ba2c99ef691f46ca4ff84316eb95', 'BCQQCHCU', 'Novelas de misterio, té', 'Yoga', 'Dulces con nuez', NULL, 'Soy alérgica al látex', 0),
(4, 1, 4, 'Carlos Méndez', 'carlos@prueba.com', '8ff2eeec3fcdcd209e9c21a63f5b3101ae40886b74344bc0399f33e23c080761', 'QB5VD9HX', NULL, NULL, NULL, NULL, NULL, 0),
(5, 1, 5, 'Mariana Torres', 'mariana@prueba.com', '226802127c419e03a2188375e831e2e5f0b11ea37ed551e4a48688169b896e9c', 'XV9BVVPB', 'Arte y manualidades', 'Fotografía', NULL, NULL, NULL, 1),
(6, 2, 1, 'Ana López', 'ana@prueba.com', 'c2e6b38e432e0c918db85a21eca4939da22d5d7838cb5b5a75a1436fbfef30d4', 'HBU6EKQE', 'Café de especialidad', NULL, 'Perfumes', NULL, NULL, 1),
(7, 2, 4, 'Carlos Méndez', 'carlos@prueba.com', 'e81b5d00169c92daca2816e58f3bc1d9049b7f326f1d26fe21806d2b1684367f', 'UDVKU5YF', 'Fútbol', 'Ciclismo', NULL, NULL, NULL, 1),
(8, 2, 6, 'Diego Herrera', 'diego@prueba.com', '7183fd08f2393d0ff97ea83833585f13523f4956d1315eb841b1c8ce1b8c5b09', 'DVVXGNDU', 'Tecnología', 'Series de ciencia ficción', 'Tazas (ya tengo muchas)', NULL, NULL, 1),
(9, 2, 5, 'Mariana Torres', 'mariana@prueba.com', '57d7e224ec3a724b1aed11802fd944649cb771eb80710fbbc95a5aa97c240920', 'ZCVBWGSY', 'Arte y manualidades', 'Fotografía', NULL, NULL, NULL, 1),
(10, 1, NULL, 'Paola Gómez', NULL, 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'PAQT2345', 'Música indie y café', 'Ilustración', NULL, NULL, 'Participa SIN cuenta: entra con su enlace personal', 0);

-- ---------- Listas de deseos ----------
INSERT INTO deseos (participante_id, titulo, descripcion, enlace, prioridad) VALUES
(1, 'Taza térmica', 'Color verde, de unos 500 ml', 'https://www.ejemplo.com/taza-termica', 1),
(1, 'Libro de recetas italianas', NULL, NULL, 2),
(2, 'Audífonos inalámbricos', 'Que sirvan para correr', 'https://www.ejemplo.com/audifonos', 1),
(3, 'Novela de Agatha Christie', 'Cualquiera que no sea "Asesinato en el Orient Express"', NULL, 1),
(3, 'Set de tés', NULL, NULL, 3),
(5, 'Cuaderno de dibujo', 'Hojas gruesas, tamaño carta', NULL, 2),
(8, 'Cable USB-C largo', '2 metros', NULL, 2),
(8, 'Libro de ciencia ficción', NULL, NULL, 1),
(9, 'Marco para fotos', NULL, NULL, 2),
(7, 'Botella para ciclismo', NULL, NULL, 1);

-- ---------- Restricción: Luis y Sofía son pareja (no se regalan entre sí) ----------
INSERT INTO restricciones (intercambio_id, dador_id, receptor_id, bidireccional) VALUES (1, 2, 3, 1);

-- ---------- Sorteo ya hecho y publicado en "Oficina 2026" ----------
-- Ana → Diego → Mariana → Carlos → Ana  (válido: nadie se regala a sí mismo)
INSERT INTO asignaciones (intercambio_id, dador_id, receptor_id) VALUES
(2, 6, 8), (2, 8, 9), (2, 9, 7), (2, 7, 6);

-- ---------- Invitación personal pendiente ----------
INSERT INTO invitaciones (intercambio_id, email, token) VALUES
(1, 'nuevo@prueba.com', 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90');

-- ---------- Algunas notificaciones ----------
INSERT INTO notificaciones (usuario_id, intercambio_id, tipo, mensaje, leida) VALUES
(1, 1, 'registro', 'Luis Pérez se unió a "Amigos 2026".', 1),
(1, 1, 'registro', 'Mariana Torres se unió a "Amigos 2026".', 0),
(1, 1, 'confirmacion', 'Mariana Torres confirmó su participación en "Amigos 2026".', 0),
(1, 2, 'revelacion', '🎁 ¡Ya puedes descubrir a tu amigo secreto de "Oficina 2026"!', 0),
(5, 2, 'revelacion', '🎁 ¡Ya puedes descubrir a tu amigo secreto de "Oficina 2026"!', 0),
(6, 2, 'revelacion', '🎁 ¡Ya puedes descubrir a tu amigo secreto de "Oficina 2026"!', 0);

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
--  Código de "Amigos 2026": NAV-AMI-GOS
-- =====================================================================

USE intercambio_navideno;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE notificaciones; TRUNCATE invitaciones; TRUNCATE asignaciones; TRUNCATE restricciones;
TRUNCATE deseos; TRUNCATE participantes; TRUNCATE intercambios; TRUNCATE usuarios;
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
INSERT INTO participantes (id, intercambio_id, usuario_id, gustos, intereses, no_le_gusta, preferencias, comentarios, confirmado) VALUES
-- Amigos 2026
(1, 1, 1, 'Café de especialidad, plantas', 'Cocinar y leer', 'Perfumes', 'Colores verdes', NULL, 1),
(2, 1, 2, 'Videojuegos y música', 'Correr', 'Ropa', 'Talla M', NULL, 1),
(3, 1, 3, 'Novelas de misterio, té', 'Yoga', 'Dulces con nuez', NULL, 'Soy alérgica al látex', 0),
(4, 1, 4, NULL, NULL, NULL, NULL, NULL, 0),
(5, 1, 5, 'Arte y manualidades', 'Fotografía', NULL, NULL, NULL, 1),
-- Oficina 2026
(6, 2, 1, 'Café de especialidad', NULL, 'Perfumes', NULL, NULL, 1),
(7, 2, 4, 'Fútbol', 'Ciclismo', NULL, NULL, NULL, 1),
(8, 2, 6, 'Tecnología', 'Series de ciencia ficción', 'Tazas (ya tengo muchas)', NULL, NULL, 1),
(9, 2, 5, 'Arte y manualidades', 'Fotografía', NULL, NULL, NULL, 1);

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

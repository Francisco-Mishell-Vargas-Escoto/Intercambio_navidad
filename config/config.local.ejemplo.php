<?php
/**
 * Archivo: config/config.local.ejemplo.php
 * PLANTILLA de configuración privada.
 *
 * Cómo usarla:
 *   1. Copia este archivo y nómbralo  config.local.php  (en esta misma carpeta).
 *   2. Cambia los valores por los de tu hosting (InfinityFree te los da en "MySQL Databases").
 *   3. config.local.php NO se sube a GitHub, así tus contraseñas no quedan públicas.
 *
 * Solo define lo que quieras cambiar; lo demás usa los valores de config.php.
 */

define('DB_HOST', 'sql123.infinityfree.com');    // "MySQL Hostname"
define('DB_NOMBRE', 'if0_12345678_intercambio');   // "MySQL DB Name"
define('DB_USUARIO', 'if0_12345678');              // "MySQL Username"
define('DB_PASSWORD', 'tu-contraseña-del-hosting'); // la contraseña de tu cuenta de hosting

// En internet NO se deben mostrar los errores técnicos a los visitantes
define('MODO_DESARROLLO', false);

// Normalmente BASE_URL se detecta sola. Descomenta solo si los enlaces salen mal:
// define('BASE_URL', '');

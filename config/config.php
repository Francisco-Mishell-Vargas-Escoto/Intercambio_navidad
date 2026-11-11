<?php
/**
 * Archivo: config/config.php
 * Configuración general de la aplicación.
 *
 * Los valores de abajo son los de XAMPP y funcionan sin cambiar nada en tu computadora.
 * Para el hosting (o si tu MySQL tiene contraseña), NO edites este archivo:
 * copia config/config.local.ejemplo.php como config/config.local.php y pon ahí tus datos.
 * config.local.php no se sube a GitHub (está en .gitignore), así tus contraseñas quedan privadas.
 */

// --- 1) Configuración local (si existe): sus valores tienen prioridad ---
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// --- Datos de la aplicación ---
define('APP_NOMBRE', 'Intercambio Navideño');

// --- Conexión a MySQL (valores por defecto de XAMPP) ---
if (!defined('DB_HOST'))     define('DB_HOST', 'localhost');
if (!defined('DB_NOMBRE'))   define('DB_NOMBRE', 'intercambio_navideno');
if (!defined('DB_USUARIO'))  define('DB_USUARIO', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');    // en XAMPP root no tiene contraseña
define('DB_CHARSET', 'utf8mb4');

// --- URL base: se detecta sola ---
// En XAMPP:   /intercambio_navideno/public/index.php  →  BASE_URL = '/intercambio_navideno'
// En hosting: /public/index.php (raíz del dominio)     →  BASE_URL = ''
if (!defined('BASE_URL')) {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = '';
    if (substr($script, -17) === '/public/index.php') {
        $base = substr($script, 0, -17);
    }
    define('BASE_URL', rtrim($base, '/'));
}

// --- Rutas físicas del proyecto ---
define('RAIZ', dirname(__DIR__));               // C:\xampp\htdocs\intercambio_navideno
define('RUTA_APP', RAIZ . '/app');
define('RUTA_VISTAS', RUTA_APP . '/views');
define('RUTA_UPLOADS', RAIZ . '/public/uploads');

// --- Zona horaria (México) ---
date_default_timezone_set('America/Mexico_City');

// --- Modo desarrollo: muestra errores. En el hosting se pone en false (config.local.php) ---
if (!defined('MODO_DESARROLLO')) define('MODO_DESARROLLO', true);
if (MODO_DESARROLLO) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

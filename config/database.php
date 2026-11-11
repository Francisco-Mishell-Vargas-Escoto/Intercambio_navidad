<?php
/**
 * Archivo: config/database.php
 * Clase Database: crea UNA sola conexión PDO y la reutiliza (patrón Singleton).
 * Uso en cualquier parte:  $pdo = Database::conectar();
 */

class Database
{
    // Aquí se guarda la conexión para no abrir una nueva en cada consulta
    private static $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOMBRE . ';charset=' . DB_CHARSET;

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // los errores lanzan excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                  // consultas preparadas reales (más seguro)
            ];

            try {
                self::$conexion = new PDO($dsn, DB_USUARIO, DB_PASSWORD, $opciones);
            } catch (PDOException $e) {
                // En la terminal (pruebas) mostramos un mensaje simple
                if (PHP_SAPI === 'cli') {
                    fwrite(STDERR, "  [ERROR] No se pudo conectar a MySQL: " . $e->getMessage() . PHP_EOL
                        . "          Enciende MySQL en XAMPP e importa database/intercambio.sql." . PHP_EOL);
                    exit(1);
                }
                // No mostramos la contraseña ni detalles sensibles al usuario final
                http_response_code(500);
                $detalle = MODO_DESARROLLO ? '<br><small>' . htmlspecialchars($e->getMessage()) . '</small>' : '';
                die('<h3 style="font-family:sans-serif;color:#a32d2d">No se pudo conectar a la base de datos.</h3>'
                    . '<p style="font-family:sans-serif">Revisa que MySQL esté encendido en XAMPP y que importaste database/intercambio.sql.' . $detalle . '</p>');
            }
        }
        return self::$conexion;
    }
}

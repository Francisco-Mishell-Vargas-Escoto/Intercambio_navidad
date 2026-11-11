<?php
/**
 * Archivo: tests/ejecutar_todas.php
 * Ejecuta todas las pruebas automáticas, una tras otra.
 *
 *   cd C:\xampp\htdocs\intercambio_navideno
 *   chcp 65001
 *   C:\xampp\php\php.exe tests\ejecutar_todas.php
 */

$pruebas = ['test_sorteo.php', 'test_validaciones.php', 'test_base_datos.php'];
$fallaron = [];

foreach ($pruebas as $archivo) {
    $comando = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . $archivo);
    passthru($comando, $codigo);
    if ($codigo !== 0) {
        $fallaron[] = $archivo;
    }
}

echo str_repeat('=', 50) . PHP_EOL;
echo $fallaron ? 'Con fallas: ' . implode(', ', $fallaron) . PHP_EOL : '¡Todas las pruebas pasaron! 🎄' . PHP_EOL;
exit($fallaron ? 1 : 0);

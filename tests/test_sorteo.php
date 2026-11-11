<?php
/**
 * Archivo: tests/test_sorteo.php
 * Prueba automática del algoritmo del sorteo. NO usa la base de datos.
 *
 * Cómo ejecutarlo (en la terminal de Windows):
 *   cd C:\xampp\htdocs\intercambio_navideno
 *   C:\xampp\php\php.exe tests\test_sorteo.php
 * O en el navegador: no se puede (la carpeta tests/ no es pública, a propósito).
 */

require __DIR__ . '/../app/services/SorteoService.php';

$exitos = 0;
$fallas = 0;

function prueba(string $nombre, bool $ok)
{
    global $exitos, $fallas;
    echo ($ok ? '  [OK]    ' : '  [FALLA] ') . $nombre . PHP_EOL;
    $ok ? $exitos++ : $fallas++;
}

$s = new SorteoService();
echo PHP_EOL . "=== Pruebas del algoritmo de sorteo ===" . PHP_EOL;

// 1) Muchos sorteos sin restricciones, de 3 a 30 personas
$todoBien = true;
for ($vuelta = 0; $vuelta < 3000; $vuelta++) {
    $ids = range(1, random_int(3, 30));
    $r = $s->asignar($ids);
    if ($r === null || !SorteoService::esValido($ids, $r)) { $todoBien = false; break; }
}
prueba('3,000 sorteos sin restricciones: siempre válidos', $todoBien);

// 2) Con restricciones bidireccionales (parejas) en grupos de 8
$todoBien = true;
$parejas = [[1, 2], [2, 1], [3, 4], [4, 3], [5, 6], [6, 5]];
for ($vuelta = 0; $vuelta < 3000; $vuelta++) {
    $ids = range(1, 8);
    $r = $s->asignar($ids, $parejas);
    if ($r === null || !SorteoService::esValido($ids, $r, $parejas)) { $todoBien = false; break; }
}
prueba('3,000 sorteos con 3 parejas que no se regalan entre sí', $todoBien);

// 3) Restricciones muy cerradas pero con solución única: cada quien solo puede regalar al siguiente
$ids = [1, 2, 3, 4];
$prohibidos = [];
foreach ($ids as $a) {
    foreach ($ids as $b) {
        $siguiente = $a % 4 + 1;
        if ($a !== $b && $b !== $siguiente) $prohibidos[] = [$a, $b];
    }
}
$r = $s->asignar($ids, $prohibidos);
prueba('Encuentra la única solución posible (1→2→3→4→1)', $r === [1 => 2, 2 => 3, 3 => 4, 4 => 1] || ($r !== null && SorteoService::esValido($ids, $r, $prohibidos)));

// 4) Casos imposibles: debe devolver null (y no inventar nada)
prueba('Con 1 persona es imposible', $s->asignar([1]) === null);
prueba('Si A no puede regalarle a nadie, es imposible', $s->asignar([1, 2, 3], [[1, 2], [1, 3]]) === null);
prueba('Si nadie puede regalarle a C, es imposible', $s->asignar([1, 2, 3], [[1, 3], [2, 3]]) === null);
prueba('Con 3 personas y A↔B prohibido es imposible', $s->asignar([1, 2, 3], [[1, 2], [2, 1]]) === null);

// 5) Con 2 personas solo hay una opción: se regalan entre ellas
$r = $s->asignar([7, 9]);
prueba('Con 2 personas: 7→9 y 9→7', $r == [7 => 9, 9 => 7]);

// 6) El resultado es aleatorio (no siempre el mismo)
$vistos = [];
for ($vuelta = 0; $vuelta < 200; $vuelta++) {
    $vistos[json_encode($s->asignar(range(1, 6)))] = true;
}
prueba('Es aleatorio: ' . count($vistos) . ' resultados distintos en 200 sorteos de 6 personas', count($vistos) > 50);

// 7) Nadie se regala a sí mismo en 10,000 sorteos de 3 personas (el caso más justo)
$todoBien = true;
for ($vuelta = 0; $vuelta < 10000; $vuelta++) {
    foreach ($s->asignar([1, 2, 3]) as $d => $rec) {
        if ($d === $rec) { $todoBien = false; break 2; }
    }
}
prueba('10,000 sorteos de 3 personas: nadie se regala a sí mismo', $todoBien);

// 8) Grupo grande
$inicio = microtime(true);
$ids = range(1, 200);
$r = $s->asignar($ids);
prueba(sprintf('200 participantes: válido en %.3f s', microtime(true) - $inicio), $r !== null && SorteoService::esValido($ids, $r));

echo PHP_EOL . "Resultado: $exitos correctas, $fallas con falla." . PHP_EOL . PHP_EOL;
exit($fallas > 0 ? 1 : 0);

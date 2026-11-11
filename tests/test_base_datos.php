<?php
/**
 * Archivo: tests/test_base_datos.php
 * Pruebas contra TU base de datos MySQL de XAMPP.
 * Todo se hace dentro de una transacción que al final se DESHACE (rollback),
 * así que no deja ningún dato guardado.
 *
 * Requisitos: MySQL encendido y database/intercambio.sql importado.
 * Ejecutar:  C:\xampp\php\php.exe tests\test_base_datos.php
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require RUTA_APP . '/services/SorteoService.php';
ini_set('display_errors', '1');

$exitos = 0;
$fallas = 0;
function prueba(string $nombre, bool $ok)
{
    global $exitos, $fallas;
    echo ($ok ? '  [OK]    ' : '  [FALLA] ') . $nombre . PHP_EOL;
    $ok ? $exitos++ : $fallas++;
}
/** Ejecuta una consulta y devuelve true si MySQL la RECHAZA (lo que esperamos). */
function rechaza(PDO $db, string $sql, array $p): bool
{
    try {
        $db->prepare($sql)->execute($p);
        return false;
    } catch (PDOException $e) {
        return true;
    }
}

echo PHP_EOL . "=== Pruebas de la base de datos ===" . PHP_EOL;
$db = Database::conectar();
echo '  Servidor: ' . $db->query('SELECT VERSION()')->fetchColumn() . PHP_EOL;
$db->beginTransaction();

try {
    // --- Datos temporales: 4 usuarios, 1 intercambio, 4 participantes ---
    $ids = [];
    for ($n = 1; $n <= 4; $n++) {
        $db->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)')
           ->execute(["Prueba $n", "prueba$n." . uniqid() . '@test.local', password_hash('x', PASSWORD_DEFAULT)]);
        $ids[] = (int)$db->lastInsertId();
    }
    prueba('Se pueden crear usuarios', count($ids) === 4);
    prueba('El correo es único', rechaza($db, 'INSERT INTO usuarios (nombre, email, password_hash) SELECT nombre, email, password_hash FROM usuarios WHERE id = ?', [$ids[0]]));

    $codigo = 'TST' . strtoupper(substr(uniqid(), -6));
    $db->prepare("INSERT INTO intercambios (admin_id, nombre, fecha_evento, fecha_limite, presupuesto_min, presupuesto_max, codigo_acceso)
                  VALUES (?, 'Prueba', NOW() + INTERVAL 30 DAY, NOW() + INTERVAL 20 DAY, 100, 200, ?)")->execute([$ids[0], $codigo]);
    $intercambio = (int)$db->lastInsertId();
    prueba('El código de acceso es único', rechaza($db,
        "INSERT INTO intercambios (admin_id, nombre, fecha_evento, fecha_limite, codigo_acceso) VALUES (?, 'Otro', NOW(), NOW(), ?)", [$ids[0], $codigo]));

    $parts = [];
    $insP = 'INSERT INTO participantes (intercambio_id, usuario_id, nombre, token, codigo) VALUES (?, ?, ?, ?, ?)';
    $codigo8 = function () { return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)); };
    foreach ($ids as $n => $u) {
        // Los dos primeros con cuenta; los otros dos SIN cuenta (usuario_id NULL), como invitados
        $db->prepare($insP)->execute([$intercambio, $n < 2 ? $u : null, "Participante $n", bin2hex(random_bytes(32)), $codigo8()]);
        $parts[] = (int)$db->lastInsertId();
    }
    prueba('Varios participantes SIN cuenta en el mismo intercambio', count($parts) === 4);
    prueba('Una misma cuenta no se puede unir dos veces al mismo intercambio', rechaza($db, $insP,
        [$intercambio, $ids[1], 'Repetido', bin2hex(random_bytes(32)), $codigo8()]));
    $tokenUsado = $db->query('SELECT token FROM participantes WHERE id = ' . $parts[3])->fetchColumn();
    prueba('El enlace personal (token) es único', rechaza($db, $insP, [$intercambio, null, 'Copia', $tokenUsado, $codigo8()]));
    $codigoUsado = $db->query('SELECT codigo FROM participantes WHERE id = ' . $parts[3])->fetchColumn();
    prueba('El código personal es único', rechaza($db, $insP, [$intercambio, null, 'Copia 2', bin2hex(random_bytes(32)), $codigoUsado]));

    // --- Sorteo real con el algoritmo y guardado en asignaciones ---
    $resultado = (new SorteoService())->asignar($parts);
    $st = $db->prepare('INSERT INTO asignaciones (intercambio_id, dador_id, receptor_id) VALUES (?, ?, ?)');
    foreach ($resultado as $d => $r) { $st->execute([$intercambio, $d, $r]); }
    $total = (int)$db->query("SELECT COUNT(*) FROM asignaciones WHERE intercambio_id = $intercambio")->fetchColumn();
    prueba('El sorteo guarda una asignación por participante', $total === 4);

    $d0 = $parts[0];
    prueba('Un participante NO puede dar dos regalos (UNIQUE dador)', rechaza($db,
        'INSERT INTO asignaciones (intercambio_id, dador_id, receptor_id) VALUES (?, ?, ?)', [$intercambio, $d0, $resultado[$parts[1]]]));
    prueba('Un participante NO puede recibir dos regalos (UNIQUE receptor)', rechaza($db,
        'INSERT INTO asignaciones (intercambio_id, dador_id, receptor_id) VALUES (?, ?, ?)', [$intercambio, $parts[1], $resultado[$d0]]));

    // La restricción CHECK (dador <> receptor) solo la respetan MariaDB 10.2+ y MySQL 8.0.16+
    $db->exec("DELETE FROM asignaciones WHERE intercambio_id = $intercambio");
    $rechazoCheck = rechaza($db, 'INSERT INTO asignaciones (intercambio_id, dador_id, receptor_id) VALUES (?, ?, ?)', [$intercambio, $d0, $d0]);
    if ($rechazoCheck) {
        prueba('La base de datos rechaza que alguien se regale a sí mismo (CHECK)', true);
    } else {
        echo "  [INFO]  Tu versión de MySQL ignora la regla CHECK; no pasa nada: el algoritmo ya lo impide." . PHP_EOL;
    }

    // --- Bloqueo del sorteo (SELECT ... FOR UPDATE) ---
    $estado = $db->query("SELECT estado FROM intercambios WHERE id = $intercambio FOR UPDATE")->fetchColumn();
    prueba('Se puede bloquear la fila del intercambio para sortear (FOR UPDATE)', $estado === 'abierto');
    $db->prepare("UPDATE intercambios SET estado = 'sorteado' WHERE id = ? AND estado IN ('abierto','cerrado')")->execute([$intercambio]);
    $st = $db->prepare("UPDATE intercambios SET estado = 'sorteado' WHERE id = ? AND estado IN ('abierto','cerrado')");
    $st->execute([$intercambio]);
    prueba('Un segundo intento de sorteo no cambia nada (0 filas afectadas)', $st->rowCount() === 0);

    // --- Borrado en cascada ---
    $db->prepare("INSERT INTO deseos (participante_id, titulo) VALUES (?, 'Algo')")->execute([$parts[2]]);
    $db->prepare('DELETE FROM participantes WHERE id = ?')->execute([$parts[2]]);
    $quedan = (int)$db->query('SELECT COUNT(*) FROM deseos WHERE participante_id = ' . $parts[2])->fetchColumn();
    prueba('Al eliminar un participante se borran sus deseos (ON DELETE CASCADE)', $quedan === 0);

    // --- Caracteres especiales ---
    $db->prepare('UPDATE usuarios SET nombre = ? WHERE id = ?')->execute(['Ñoño Pérez 🎄', $ids[3]]);
    $nombre = $db->query('SELECT nombre FROM usuarios WHERE id = ' . $ids[3])->fetchColumn();
    prueba('Guarda acentos, ñ y emojis correctamente (utf8mb4)', $nombre === 'Ñoño Pérez 🎄');

    // --- Inyección SQL con consultas preparadas ---
    $malicioso = "x' OR '1'='1";
    $st = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
    $st->execute([$malicioso]);
    prueba('Las consultas preparadas neutralizan la inyección SQL', (int)$st->fetchColumn() === 0);
} catch (Exception $e) {
    prueba('Error inesperado: ' . $e->getMessage(), false);
}

$db->rollBack();   // ← deshace TODO: tu base de datos queda igual que antes
echo '  (Se deshicieron todos los cambios de prueba.)' . PHP_EOL;
echo PHP_EOL . "Resultado: $exitos correctas, $fallas con falla." . PHP_EOL . PHP_EOL;
exit($fallas > 0 ? 1 : 0);

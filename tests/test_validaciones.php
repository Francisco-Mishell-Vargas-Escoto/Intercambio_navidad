<?php
/**
 * Archivo: tests/test_validaciones.php
 * Pruebas de validación, códigos, escape de HTML y subida de archivos.
 * NO usa la base de datos.
 *
 * Ejecutar:  C:\xampp\php\php.exe tests\test_validaciones.php
 */

define('BASE_URL', '/intercambio_navideno');
define('RUTA_APP', __DIR__ . '/../app');
define('RUTA_UPLOADS', sys_get_temp_dir());
date_default_timezone_set('America/Mexico_City');
require RUTA_APP . '/core/helpers.php';
require RUTA_APP . '/core/Validator.php';
require RUTA_APP . '/services/CodigoService.php';
require RUTA_APP . '/services/IntercambioValidacion.php';
require RUTA_APP . '/services/UploadService.php';

$exitos = 0;
$fallas = 0;
function prueba(string $nombre, bool $ok)
{
    global $exitos, $fallas;
    echo ($ok ? '  [OK]    ' : '  [FALLA] ') . $nombre . PHP_EOL;
    $ok ? $exitos++ : $fallas++;
}

/* ---------- Validator ---------- */
echo PHP_EOL . "=== Validación de formularios (backend) ===" . PHP_EOL;
$v = (new Validator(['nombre' => '  ', 'email' => 'no-es-correo']))->requerido('nombre', 'El nombre')->email('email');
prueba('Campo con solo espacios cuenta como vacío', isset($v->errores()['nombre']));
prueba('Rechaza un correo inválido', isset($v->errores()['email']));
prueba('Acepta un correo válido', !(new Validator(['email' => 'ana@prueba.com']))->email('email')->falla());
prueba('Contraseña corta rechazada', (new Validator(['p' => 'abc12']))->password('p')->falla());
prueba('Contraseña sin números rechazada', (new Validator(['p' => 'soloLetras']))->password('p')->falla());
prueba('Contraseña segura aceptada', !(new Validator(['p' => 'Navidad2026']))->password('p')->falla());
prueba('Detecta contraseñas distintas', (new Validator(['a' => 'x1234567', 'b' => 'y1234567']))->igual('a', 'b', 'no')->falla());
prueba('Respeta el máximo de caracteres (con acentos)', !(new Validator(['n' => str_repeat('ñ', 10)]))->max('n', 10, 'N')->falla());
prueba('Un arreglo en lugar de texto no rompe la validación', (new Validator(['n' => ['x']]))->requerido('n', 'N')->falla());

/* ---------- Validación de intercambios ---------- */
echo PHP_EOL . "=== Validación de intercambios ===" . PHP_EOL;
$futuro = date('Y-m-d\TH:i', strtotime('+30 days'));
$antes  = date('Y-m-d\TH:i', strtotime('+20 days'));
$base = ['nombre' => 'Amigos', 'fecha_evento' => $futuro, 'fecha_limite' => $antes, 'presupuesto_min' => '300', 'presupuesto_max' => '500'];

list($d, $v) = IntercambioValidacion::validar($base);
prueba('Datos correctos pasan y se convierten a formato MySQL', !$v->falla() && $d['fecha_evento'] === date('Y-m-d H:i:00', strtotime($futuro)));
list(, $v) = IntercambioValidacion::validar(['presupuesto_min' => '600'] + $base);
prueba('Mínimo mayor que máximo: rechazado', isset($v->errores()['presupuesto_max']));
list(, $v) = IntercambioValidacion::validar(['presupuesto_min' => '-5'] + $base);
prueba('Presupuesto negativo: rechazado', isset($v->errores()['presupuesto_min']));
list(, $v) = IntercambioValidacion::validar(['presupuesto_max' => 'mucho'] + $base);
prueba('Presupuesto que no es número: rechazado', isset($v->errores()['presupuesto_max']));
list(, $v) = IntercambioValidacion::validar(['fecha_evento' => date('Y-m-d\TH:i', strtotime('-1 day'))] + $base);
prueba('Fecha del evento en el pasado: rechazada', isset($v->errores()['fecha_evento']));
list(, $v) = IntercambioValidacion::validar(['fecha_limite' => date('Y-m-d\TH:i', strtotime('+31 days'))] + $base);
prueba('Fecha límite después del evento: rechazada', isset($v->errores()['fecha_limite']));
list(, $v) = IntercambioValidacion::validar(['fecha_evento' => '2026-02-31T10:00'] + $base);
prueba('Fecha inexistente (31 de febrero): rechazada', isset($v->errores()['fecha_evento']));
list(, $v) = IntercambioValidacion::validar(['fecha_evento' => date('Y-m-d\TH:i:s', strtotime('+30 days'))] + $base);
prueba('Acepta fechas con segundos (algunos navegadores las envían así)', !isset($v->errores()['fecha_evento']));

/* ---------- Códigos de acceso ---------- */
echo PHP_EOL . "=== Códigos de acceso ===" . PHP_EOL;
prueba('Normaliza lo que escribe el usuario: "nav-7k2 q9x" → NAV7K2Q9X', CodigoService::normalizar('nav-7k2 q9x') === 'NAV7K2Q9X');
prueba('Formatea para mostrar: NAV7K2Q9X → NAV-7K2-Q9X', CodigoService::formatear('NAV7K2Q9X') === 'NAV-7K2-Q9X');
$ok = true;
for ($n = 0; $n < 2000; $n++) {
    if (preg_match('/[01OIL]/', CodigoService::aleatorio(6))) { $ok = false; break; }
}
prueba('2,000 códigos sin caracteres confusos (0, 1, O, I, L)', $ok);
$vistos = [];
for ($n = 0; $n < 2000; $n++) { $vistos[CodigoService::aleatorio(6)] = true; }
prueba('2,000 códigos aleatorios distintos: ' . count($vistos), count($vistos) >= 1995);

/* ---------- Escape de HTML (protección XSS) ---------- */
echo PHP_EOL . "=== Protección contra XSS ===" . PHP_EOL;
prueba('e() escapa <script>', e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;');
prueba('e() escapa comillas (atributos HTML)', e('" onmouseover="x') === '&quot; onmouseover=&quot;x');
prueba('e() acepta null sin error', e(null) === '');
prueba('iniciales() con acentos: "Ángel Núñez" → ÁN', iniciales('Ángel Núñez') === 'ÁN');

/* ---------- Subida de archivos ---------- */
echo PHP_EOL . "=== Subida de imágenes ===" . PHP_EOL;
$r = UploadService::imagen(['error' => UPLOAD_ERR_NO_FILE], 'fotos');
prueba('Sin archivo: es opcional, no marca error', $r['archivo'] === null && $r['error'] === null);
$r = UploadService::imagen(['error' => UPLOAD_ERR_INI_SIZE], 'fotos');
prueba('Archivo demasiado grande: rechazado', $r['error'] !== null);
$falso = tempnam(sys_get_temp_dir(), 'php');
file_put_contents($falso, '<?php echo "hackeado"; ?>');
$r = UploadService::imagen(['error' => UPLOAD_ERR_OK, 'tmp_name' => $falso, 'size' => 30, 'name' => 'foto.jpg'], 'fotos');
prueba('Un PHP disfrazado de .jpg (no subido por formulario): rechazado', $r['archivo'] === null && $r['error'] !== null);
unlink($falso);

echo PHP_EOL . "Resultado: $exitos correctas, $fallas con falla." . PHP_EOL . PHP_EOL;
exit($fallas > 0 ? 1 : 0);

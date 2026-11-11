<?php
/**
 * Archivo: app/core/helpers.php
 * Funciones pequeñas que usamos en todo el proyecto.
 */

/** Escapa texto antes de imprimirlo en HTML (evita ataques XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/** Construye una URL interna. url('login') => /intercambio_navideno/login */
function url(string $ruta = ''): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

/** URL de un archivo estático. asset('css/estilos.css') */
function asset(string $ruta): string
{
    return BASE_URL . '/assets/' . ltrim($ruta, '/');
}

/** Redirige a otra página y detiene la ejecución. */
function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/**
 * Mensajes "flash": se guardan en la sesión y se muestran UNA vez.
 * flash('exito', 'Guardado')  -> guarda
 * flash()                     -> devuelve todos y los borra
 */
function flash(?string $tipo = null, ?string $mensaje = null): array
{
    if ($tipo !== null && $mensaje !== null) {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        return [];
    }
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/**
 * Devuelve lo que el usuario había escrito en un campo antes de un error,
 * para no obligarlo a escribir todo otra vez.  value="<?= old('email') ?>"
 */
function old(string $campo): string
{
    static $old = null;
    if ($old === null) {                 // se lee de la sesión una sola vez
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
    }
    $valor = $old[$campo] ?? '';
    return e(is_string($valor) ? $valor : '');
}

/** Mensaje de error del servidor para un campo (o cadena vacía). */
function error_campo(string $campo): string
{
    static $errores = null;
    if ($errores === null) {
        $errores = $_SESSION['errores'] ?? [];
        unset($_SESSION['errores']);
    }
    return $errores[$campo] ?? '';
}

/** Clase de Bootstrap para marcar un campo con error en rojo. */
function clase_error(string $campo): string
{
    return error_campo($campo) !== '' ? ' is-invalid' : '';
}

/** Lista de mensajes navideños (se usan en el pie de página y en la página de inicio). */
function mensajes_navidenos(): array
{
    return [
        'Que la magia de la Navidad llene tu hogar de alegría.',
        'El mejor regalo es compartir con quienes quieres.',
        'Jo, jo, jo… ¡que no se te olvide tu regalo!',
        'Que cada regalo venga envuelto en una sonrisa.',
        'Navidad es dar sin esperar, pero recibir también se vale.',
        'Que tu lista de deseos se cumpla este año.',
        'Un poquito de chocolate caliente arregla cualquier día.',
        'Brilla más que las luces del árbol.',
        'Los mejores regalos no caben en una caja: son los momentos juntos.',
        'Que no te falten abrazos, ni ponche, ni buenos amigos.',
        'Envolver regalos es un arte; la cinta adhesiva, tu mejor aliada.',
        'Que el espíritu navideño te acompañe todo el año.',
        'Un amigo secreto es un amigo que te conoce más de lo que crees.',
        'Hoy es un buen día para cantar un villancico (aunque sea bajito).',
        'Detrás de cada regalo hay alguien que pensó en ti.',
    ];
}

/** Devuelve un mensaje navideño al azar. */
function mensaje_navideno(): string
{
    $mensajes = mensajes_navidenos();
    return $mensajes[array_rand($mensajes)];
}

/** Formatea una fecha de MySQL a formato legible en español: 20 dic 2026 */
function fecha_corta(?string $fecha): string
{
    if (!$fecha) return '';
    $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

/** Fecha con hora: 20 dic 2026, 19:00 */
function fecha_hora(?string $fecha): string
{
    return $fecha ? fecha_corta($fecha) . ', ' . date('H:i', strtotime($fecha)) : '';
}

/** Fecha en el formato que entiende JavaScript (para la cuenta regresiva). */
function fecha_js(string $fecha): string
{
    return date('Y-m-d\TH:i:s', strtotime($fecha));
}

/** Texto y color del estado de un intercambio. */
function estado_intercambio(array $i): array
{
    if ($i['estado'] === 'abierto' && strtotime($i['fecha_limite']) < time()) {
        return ['Registro cerrado', 'secondary'];
    }
    $mapa = [
        'abierto'  => ['Registro abierto', 'success'],
        'cerrado'  => ['Registro cerrado', 'secondary'],
        'sorteado' => ['Sorteo realizado', 'warning'],
        'revelado' => ['Resultados publicados', 'danger'],
    ];
    return $mapa[$i['estado']] ?? ['—', 'secondary'];
}

/** Rango de presupuesto: $300.00 – $500.00 */
function presupuesto(array $i): string
{
    return dinero($i['presupuesto_min']) . ' – ' . dinero($i['presupuesto_max']);
}

/** Iniciales para el avatar cuando no hay foto: "Ana López" => "AL" */
function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre));
    $ini = mb_substr($partes[0], 0, 1) . (isset($partes[1]) ? mb_substr($partes[1], 0, 1) : '');
    return mb_strtoupper($ini);
}

/** Formatea dinero: 350 => $350.00 */
function dinero($cantidad): string
{
    return '$' . number_format((float)$cantidad, 2);
}

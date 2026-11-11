<?php
/**
 * Archivo: app/core/Participacion.php
 * ¿Quién es el participante que está viendo la página?
 *
 * Los participantes NO necesitan cuenta. Se identifican de dos formas:
 *  1. Con su ENLACE PERSONAL  /p/{token}  → al abrirlo, el navegador guarda una cookie.
 *  2. Si tienen cuenta e iniciaron sesión (por ejemplo, el organizador que también participa).
 *
 * La cookie se llama "inp_{id del intercambio}", guarda el token, dura 1 año
 * y es HttpOnly (JavaScript no puede leerla).
 */

require_once RUTA_APP . '/models/Participante.php';

class Participacion
{
    const COOKIE = 'inp_';
    const DURACION = 31536000;   // 1 año en segundos

    /** Participante actual en ESE intercambio (o null si no participa / no se identificó). */
    public static function actual(int $intercambioId): ?array
    {
        $modelo = new Participante();

        if (Auth::check()) {
            $p = $modelo->buscarPorUsuario($intercambioId, Auth::id());
            if ($p) return $p;
        }

        $token = self::tokenDeCookie($intercambioId);
        if ($token) {
            $p = $modelo->buscarPorToken($token);
            // El token debe ser de ESTE intercambio
            if ($p && (int)$p['intercambio_id'] === $intercambioId) return $p;
        }
        return null;
    }

    /** Guarda la cookie para que este navegador recuerde al participante. */
    public static function recordar(array $p): void
    {
        $nombre = self::COOKIE . (int)$p['intercambio_id'];
        $seguro = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        // Firma compatible con PHP 7.2: nombre, valor, expira, ruta, dominio, solo https, httponly
        setcookie($nombre, $p['token'], time() + self::DURACION, (BASE_URL ?: '') . '/', '', $seguro, true);
        $_COOKIE[$nombre] = $p['token'];
    }

    /** Borra la cookie de un intercambio ("No soy yo" / salir en este dispositivo). */
    public static function olvidar(int $intercambioId): void
    {
        $nombre = self::COOKIE . $intercambioId;
        setcookie($nombre, '', time() - 3600, (BASE_URL ?: '') . '/');
        unset($_COOKIE[$nombre]);
    }

    /** Participaciones guardadas en ESTE navegador (para mostrarlas en el inicio). */
    public static function delDispositivo(): array
    {
        $modelo = new Participante();
        $lista = [];
        foreach ($_COOKIE as $nombre => $valor) {
            if (strpos($nombre, self::COOKIE) !== 0 || !is_string($valor) || !preg_match('/^[a-f0-9]{64}$/', $valor)) {
                continue;
            }
            $p = $modelo->buscarPorToken($valor);
            if ($p && self::COOKIE . $p['intercambio_id'] === $nombre) {
                $lista[] = $p;
            }
        }
        return $lista;
    }

    /** Enlace personal completo: http://dominio/p/{token} */
    public static function enlace(array $p): string
    {
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $esquema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('p/' . $p['token']);
    }

    private static function tokenDeCookie(int $intercambioId): ?string
    {
        $t = $_COOKIE[self::COOKIE . $intercambioId] ?? '';
        return (is_string($t) && preg_match('/^[a-f0-9]{64}$/', $t)) ? $t : null;
    }
}

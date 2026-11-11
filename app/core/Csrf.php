<?php
/**
 * Archivo: app/core/Csrf.php
 * Protección CSRF: cada formulario lleva un token secreto guardado en la sesión.
 * Si alguien intenta enviar un formulario desde otra página, el token no coincide
 * y la petición se rechaza.
 */

class Csrf
{
    /** Devuelve el token de la sesión (lo crea si no existe). */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Imprime el campo oculto que va dentro de cada <form method="post">. */
    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    /** Verifica el token del formulario. Si no es válido, detiene todo. */
    public static function verificar(): void
    {
        $enviado = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($enviado) || !hash_equals(self::token(), $enviado)) {
            http_response_code(419);
            die('<p style="font-family:sans-serif">La sesión del formulario expiró. '
                . '<a href="javascript:history.back()">Regresa</a>, recarga la página e inténtalo de nuevo.</p>');
        }
    }
}

<?php
/**
 * Archivo: app/core/Auth.php
 * Manejo de la sesión del usuario: iniciar, consultar y cerrar sesión.
 */

class Auth
{
    /** Guarda al usuario en la sesión después de validar su contraseña. */
    public static function iniciar(array $usuario): void
    {
        // Nuevo ID de sesión: evita el ataque de "fijación de sesión"
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'     => (int)$usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'foto'   => $usuario['foto'],
        ];
        $_SESSION['ultima_actividad'] = time();
    }

    /** ¿Hay alguien con sesión iniciada? */
    public static function check(): bool
    {
        return isset($_SESSION['usuario']);
    }

    /** Datos del usuario en sesión (o null). */
    public static function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    /** ID del usuario en sesión (o 0). */
    public static function id(): int
    {
        return $_SESSION['usuario']['id'] ?? 0;
    }

    /**
     * Protege una página: si no hay sesión, manda al login
     * y recuerda a dónde quería ir el usuario.
     */
    public static function requerir(): void
    {
        if (!self::check()) {
            $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'];
            flash('warning', 'Inicia sesión para continuar.');
            redirigir('login');
        }
    }

    /**
     * Cierra la sesión si el usuario estuvo inactivo demasiado tiempo.
     * Se llama en cada petición desde public/index.php.
     */
    public static function revisarInactividad(int $minutos = 120): void
    {
        if (self::check() && isset($_SESSION['ultima_actividad'])
            && time() - $_SESSION['ultima_actividad'] > $minutos * 60) {
            self::cerrar();
            session_start();
            flash('warning', 'Tu sesión expiró por inactividad. Vuelve a entrar.');
        }
        $_SESSION['ultima_actividad'] = time();
    }

    /** Borra todo rastro de la sesión. */
    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

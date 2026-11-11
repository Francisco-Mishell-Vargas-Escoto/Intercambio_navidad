<?php
/**
 * Archivo: app/controllers/AuthController.php
 * Registro, inicio y cierre de sesión.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/core/Validator.php';
require_once RUTA_APP . '/models/Usuario.php';

class AuthController extends Controller
{
    // Límite de intentos fallidos de login antes de bloquear temporalmente
    const MAX_INTENTOS = 5;
    const MINUTOS_BLOQUEO = 5;

    /* ===================== REGISTRO ===================== */

    /** GET /registro */
    public function mostrarRegistro(): void
    {
        if (Auth::check()) redirigir('mis-intercambios');
        $this->vista('auth/registro', ['titulo' => 'Crear cuenta']);
    }

    /** POST /registro */
    public function registrar(): void
    {
        Csrf::verificar();

        $v = new Validator($_POST);
        $v->requerido('nombre', 'El nombre')->min('nombre', 3, 'El nombre')->max('nombre', 120, 'El nombre')
          ->requerido('email', 'El correo')->email('email')->max('email', 150, 'El correo')
          ->password('password')
          ->igual('password', 'confirmar_password', 'Las contraseñas no coinciden.');

        $modelo = new Usuario();
        if (!$v->falla() && $modelo->buscarPorEmail($v->valor('email'))) {
            $v->error('email', 'Ya existe una cuenta con ese correo. ¿Quieres iniciar sesión?');
        }

        if ($v->falla()) {
            $this->regresarConErrores('registro', $v->errores(), ['nombre', 'email']);
        }

        $id = $modelo->crear($v->valor('nombre'), $v->valor('email'), $_POST['password']);
        Auth::iniciar($modelo->buscarPorId($id));

        flash('success', '¡Bienvenido, ' . $v->valor('nombre') . '! Tu cuenta está lista.');
        $this->irADestino();
    }

    /* ===================== LOGIN ===================== */

    /** GET /login */
    public function mostrarLogin(): void
    {
        if (Auth::check()) redirigir('mis-intercambios');
        $this->vista('auth/login', ['titulo' => 'Iniciar sesión']);
    }

    /** POST /login */
    public function login(): void
    {
        Csrf::verificar();

        // ¿Está bloqueado por demasiados intentos?
        $bloqueo = $_SESSION['login_bloqueado_hasta'] ?? 0;
        if ($bloqueo > time()) {
            $min = (int)ceil(($bloqueo - time()) / 60);
            flash('error', "Demasiados intentos. Espera $min minuto(s) e inténtalo de nuevo.");
            redirigir('login');
        }

        $v = new Validator($_POST);
        $v->requerido('email', 'El correo')->email('email')->requerido('password', 'La contraseña');
        if ($v->falla()) {
            $this->regresarConErrores('login', $v->errores(), ['email']);
        }

        $modelo = new Usuario();
        $usuario = $modelo->buscarPorEmail($v->valor('email'));
        $password = $_POST['password'];

        // Mismo mensaje si el correo no existe o la contraseña es incorrecta:
        // así nadie puede averiguar qué correos están registrados.
        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            $_SESSION['login_intentos'] = ($_SESSION['login_intentos'] ?? 0) + 1;
            if ($_SESSION['login_intentos'] >= self::MAX_INTENTOS) {
                $_SESSION['login_bloqueado_hasta'] = time() + self::MINUTOS_BLOQUEO * 60;
                $_SESSION['login_intentos'] = 0;
            }
            $this->regresarConErrores('login', ['password' => 'Correo o contraseña incorrectos.'], ['email']);
        }

        if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
            $modelo->actualizarHash((int)$usuario['id'], $password);
        }

        unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
        Auth::iniciar($usuario);
        flash('success', '¡Hola de nuevo, ' . $usuario['nombre'] . '!');
        $this->irADestino();
    }

    /* ===================== LOGOUT ===================== */

    /** POST /logout  (es POST para que un enlace malicioso no pueda cerrarte la sesión) */
    public function logout(): void
    {
        Csrf::verificar();
        Auth::cerrar();
        session_start();
        flash('info', 'Cerraste sesión. ¡Felices fiestas!');
        redirigir('/');
    }

    /* ===================== AYUDANTES ===================== */

    /** Después de entrar, lleva al usuario a donde quería ir (o a su panel). */
    private function irADestino(): void
    {
        $destino = $_SESSION['url_destino'] ?? null;
        unset($_SESSION['url_destino']);

        // Solo aceptamos rutas internas del proyecto (evita redirecciones a sitios externos)
        if ($destino && strpos($destino, BASE_URL . '/') === 0) {
            header('Location: ' . $destino);
            exit;
        }
        redirigir('mis-intercambios');
    }
}

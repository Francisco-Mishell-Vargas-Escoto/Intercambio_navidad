<?php
/**
 * Archivo: app/controllers/IntercambioController.php
 * Crear intercambios, verlos y unirse con código o enlace.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';
require_once RUTA_APP . '/core/Validator.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/models/Participante.php';
require_once RUTA_APP . '/models/Deseo.php';
require_once RUTA_APP . '/core/Participacion.php';
require_once RUTA_APP . '/services/CodigoService.php';
require_once RUTA_APP . '/services/IntercambioValidacion.php';

class IntercambioController extends Controller
{
    /* ===================== CREAR ===================== */

    /** GET /intercambios/crear */
    public function crear(): void
    {
        Auth::requerir();
        $this->vista('intercambio/crear', ['titulo' => 'Crear intercambio']);
    }

    /** POST /intercambios/crear */
    public function guardar(): void
    {
        Auth::requerir();
        Csrf::verificar();

        // Las reglas de validación están en app/services/IntercambioValidacion.php
        list($datos, $v) = IntercambioValidacion::validar($_POST);
        if ($v->falla()) {
            $this->regresarConErrores('intercambios/crear', $v->errores(), IntercambioValidacion::CAMPOS);
        }

        // --- Guardar: intercambio + (opcional) el admin como participante, en una transacción ---
        $db = Database::conectar();
        $db->beginTransaction();
        try {
            $modelo = new Intercambio();
            $datos['admin_id'] = Auth::id();
            $datos['codigo_acceso'] = CodigoService::generarUnico($db);
            $id = $modelo->crear($datos);

            if (!empty($_POST['participa'])) {
                (new Participante())->unirCuenta($id, Auth::usuario());
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        flash('success', '¡Tu intercambio está listo! Comparte el código o el enlace con tus amigos.');
        redirigir('intercambios/' . $id);
    }

    /* ===================== VER ===================== */

    /**
     * GET /intercambios/{id}
     * Lo ven el organizador (con sesión) y los participantes (con su enlace personal o su cuenta).
     */
    public function ver(string $id): void
    {
        $i = $this->cargarConAcceso((int)$id);
        $participante = Participacion::actual((int)$i['id']);

        $this->vista('intercambio/ver', [
            'titulo'        => $i['nombre'],
            'i'             => $i,
            'esAdmin'       => Auth::check() && (int)$i['admin_id'] === Auth::id(),
            'participante'  => $participante,
            'enlacePersonal'=> $participante ? Participacion::enlace($participante) : null,
            'misDeseos'     => $participante ? (new Deseo())->contar((int)$participante['id']) : 0,
            'participantes' => (new Intercambio())->nombresParticipantes((int)$i['id']),
            'enlace'        => self::enlaceInvitacion($i['codigo_acceso']),
        ]);
    }

    /* ===================== ENLACE PERSONAL ===================== */

    /** GET /p/{token}  — el participante abre su enlace personal: lo recordamos y lo llevamos a su intercambio */
    public function enlacePersonal(string $token): void
    {
        $p = preg_match('/^[a-f0-9]{64}$/', $token) ? (new Participante())->buscarPorToken($token) : null;
        if (!$p) {
            flash('error', 'Ese enlace personal no es válido. Pide al organizador que te lo reenvíe.');
            redirigir('/');
        }
        Participacion::recordar($p);
        redirigir('intercambios/' . $p['intercambio_id']);
    }

    /** GET /entrar  — formulario para entrar con el código personal */
    public function formularioEntrar(): void
    {
        $this->vista('intercambio/entrar', ['titulo' => 'Entrar con mi código']);
    }

    /**
     * POST /entrar  — entra con el código personal (ej. K7M2-QX9P).
     * Protección contra adivinar códigos: máximo 5 intentos fallidos cada 10 minutos.
     */
    public function entrar(): void
    {
        Csrf::verificar();

        $bloqueo = $_SESSION['entrar_bloqueado_hasta'] ?? 0;
        if ($bloqueo > time()) {
            $min = (int)ceil(($bloqueo - time()) / 60);
            $this->regresarConErrores('entrar', ['codigo' => "Demasiados intentos. Espera $min minuto(s) e inténtalo de nuevo."], []);
        }

        $codigo = CodigoService::normalizar((string)($_POST['codigo'] ?? ''));
        $p = strlen($codigo) === 8 ? (new Participante())->buscarPorCodigo($codigo) : null;

        if (!$p) {
            usleep(400000);   // frena a quien intente probar códigos al azar
            $_SESSION['entrar_intentos'] = ($_SESSION['entrar_intentos'] ?? 0) + 1;
            if ($_SESSION['entrar_intentos'] >= 5) {
                $_SESSION['entrar_bloqueado_hasta'] = time() + 600;
                $_SESSION['entrar_intentos'] = 0;
            }
            $this->regresarConErrores('entrar', ['codigo' => 'Ese código no existe. Revísalo (son 8 caracteres) o pide al organizador que te lo reenvíe.'], ['codigo']);
        }

        unset($_SESSION['entrar_intentos'], $_SESSION['entrar_bloqueado_hasta']);
        Participacion::recordar($p);
        flash('success', '¡Hola de nuevo, ' . explode(' ', $p['nombre'])[0] . '!');
        redirigir('intercambios/' . $p['intercambio_id']);
    }

    /** POST /intercambios/{id}/salir  — "No soy yo": este navegador olvida al participante */
    public function olvidarDispositivo(string $id): void
    {
        Csrf::verificar();
        Participacion::olvidar((int)$id);
        flash('info', 'Listo: este navegador ya no recuerda tu participación. Puedes volver a entrar con tu código personal o tu enlace.');
        redirigir('/');
    }

    /* ===================== UNIRSE (sin cuenta) ===================== */

    /** GET /unirse  — formulario para escribir el código */
    public function formularioUnirse(): void
    {
        $this->vista('intercambio/unirse', ['titulo' => 'Unirme a un intercambio']);
    }

    /** POST /unirse  — revisa el código y manda a la página de confirmación */
    public function buscarCodigo(): void
    {
        Csrf::verificar();

        $codigo = CodigoService::normalizar((string)($_POST['codigo'] ?? ''));
        $i = $codigo !== '' ? (new Intercambio())->buscarPorCodigo($codigo) : null;

        if (!$i) {
            $this->regresarConErrores('unirse', ['codigo' => 'No encontramos un intercambio con ese código. Revísalo e inténtalo de nuevo.'], ['codigo']);
        }
        redirigir('unirse/' . $codigo);
    }

    /** GET /unirse/{codigo}  — el enlace general de invitación llega aquí */
    public function confirmarUnirse(string $codigo): void
    {
        $codigo = CodigoService::normalizar($codigo);
        $i = (new Intercambio())->buscarPorCodigo($codigo);
        if (!$i) {
            flash('error', 'El enlace de invitación no es válido.');
            redirigir('unirse');
        }

        // Si este navegador (o su cuenta) ya participa, directo a la página del intercambio
        if (Participacion::actual((int)$i['id'])) {
            flash('info', 'Ya formas parte de este intercambio.');
            redirigir('intercambios/' . $i['id']);
        }

        $this->vista('intercambio/confirmar-unirse', [
            'titulo'  => 'Unirme a ' . $i['nombre'],
            'i'       => $i,
            'abierto' => Intercambio::registroAbierto($i),
            'accion'  => url('unirse/' . $i['codigo_acceso']),
        ]);
    }

    /** POST /unirse/{codigo}  — se une con su nombre (o con su cuenta si inició sesión) */
    public function unirse(string $codigo): void
    {
        Csrf::verificar();

        $codigo = CodigoService::normalizar($codigo);
        $i = (new Intercambio())->buscarPorCodigo($codigo);
        if (!$i) {
            flash('error', 'El intercambio no existe.');
            redirigir('unirse');
        }
        if (!Intercambio::registroAbierto($i)) {
            flash('warning', 'El registro de este intercambio ya está cerrado. Habla con el organizador.');
            redirigir('unirse/' . $codigo);
        }

        $p = self::registrarParticipante($i, 'unirse/' . $codigo);
        flash('success', '¡Ya estás dentro de "' . $i['nombre'] . '"! Guarda tu enlace personal y completa tu lista de deseos.');
        redirigir('intercambios/' . $i['id']);
    }

    /**
     * Crea la participación a partir del formulario (nombre + correo opcional),
     * la recuerda en este navegador y avisa al organizador.
     * Lo usan "unirse con código" y "aceptar invitación personal".
     */
    public static function registrarParticipante(array $i, string $rutaError, ?string $emailInvitacion = null): array
    {
        $modelo = new Participante();
        $yaEsta = Participacion::actual((int)$i['id']);
        if ($yaEsta) {
            return $yaEsta;
        }

        if (Auth::check()) {
            // Con sesión: se une con su cuenta, sin escribir nada
            $p = $modelo->unirCuenta((int)$i['id'], Auth::usuario());
        } else {
            $v = new Validator($_POST);
            $v->requerido('nombre', 'Tu nombre')->min('nombre', 2, 'Tu nombre')->max('nombre', 120, 'Tu nombre')
              ->email('email')->max('email', 150, 'El correo');
            if (!$v->falla() && $modelo->existeNombre((int)$i['id'], $v->valor('nombre'))) {
                $v->error('nombre', 'Ya hay alguien con ese nombre. Si eres tú, abre tu enlace personal (el organizador puede reenviártelo); si no, agrega tu apellido.');
            }
            if ($v->falla()) {
                $_SESSION['errores'] = $v->errores();
                $_SESSION['old'] = array_intersect_key($_POST, array_flip(['nombre', 'email']));
                redirigir($rutaError);
            }
            $p = $modelo->crear((int)$i['id'], $v->valor('nombre'), $v->valor('email') ?: $emailInvitacion);
        }

        Participacion::recordar($p);
        NotificacionService::nuevoParticipante($i, $p);
        $_SESSION['recien_unido'] = (int)$i['id'];   // para resaltar el enlace personal en la página
        return $p;
    }

    /* ===================== AYUDANTES ===================== */

    /**
     * Carga el intercambio y verifica que quien lo ve sea el organizador o un participante.
     * Si no, responde 404 (no revelamos que el intercambio existe).
     */
    private function cargarConAcceso(int $id): array
    {
        $i = (new Intercambio())->buscarPorId($id);
        $esAdmin = $i && Auth::check() && (int)$i['admin_id'] === Auth::id();
        if (!$i || (!$esAdmin && !Participacion::actual($id))) {
            http_response_code(404);
            $this->vista('errors/404', ['titulo' => 'No encontrado']);
            exit;
        }
        return $i;
    }

    /** Enlace completo para compartir: http://localhost/intercambio_navideno/unirse/NAV7K2Q9X */
    public static function enlaceInvitacion(string $codigo): string
    {
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $esquema . '://' . $_SERVER['HTTP_HOST'] . url('unirse/' . $codigo);
    }
}

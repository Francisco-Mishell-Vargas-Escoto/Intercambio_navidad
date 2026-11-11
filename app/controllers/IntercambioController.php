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
                (new Participante())->unir($id, Auth::id());
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

    /** GET /intercambios/{id}  — solo para el admin o participantes */
    public function ver(string $id): void
    {
        Auth::requerir();
        $i = $this->cargarConAcceso((int)$id);

        $participante = (new Participante())->buscar((int)$i['id'], Auth::id());

        $this->vista('intercambio/ver', [
            'titulo'        => $i['nombre'],
            'i'             => $i,
            'esAdmin'       => (int)$i['admin_id'] === Auth::id(),
            'participante'  => $participante,
            'misDeseos'     => $participante ? (new Deseo())->contar((int)$participante['id']) : 0,
            'participantes' => (new Intercambio())->nombresParticipantes((int)$i['id']),
            'enlace'        => self::enlaceInvitacion($i['codigo_acceso']),
        ]);
    }

    /* ===================== UNIRSE ===================== */

    /** GET /unirse  — formulario para escribir el código */
    public function formularioUnirse(): void
    {
        Auth::requerir();
        $this->vista('intercambio/unirse', ['titulo' => 'Unirme a un intercambio']);
    }

    /** POST /unirse  — revisa el código y manda a la página de confirmación */
    public function buscarCodigo(): void
    {
        Auth::requerir();
        Csrf::verificar();

        $codigo = CodigoService::normalizar((string)($_POST['codigo'] ?? ''));
        $i = $codigo !== '' ? (new Intercambio())->buscarPorCodigo($codigo) : null;

        if (!$i) {
            $this->regresarConErrores('unirse', ['codigo' => 'No encontramos un intercambio con ese código. Revísalo e inténtalo de nuevo.'], ['codigo']);
        }
        redirigir('unirse/' . $codigo);
    }

    /** GET /unirse/{codigo}  — el enlace de invitación llega aquí */
    public function confirmarUnirse(string $codigo): void
    {
        Auth::requerir();   // si no tiene sesión, después del login regresa a este mismo enlace
        $codigo = CodigoService::normalizar($codigo);
        $i = (new Intercambio())->buscarPorCodigo($codigo);
        if (!$i) {
            flash('error', 'El enlace de invitación no es válido.');
            redirigir('unirse');
        }

        // Si ya participa, lo mandamos directo a la página del intercambio
        if ((new Participante())->buscar((int)$i['id'], Auth::id())) {
            flash('info', 'Ya formas parte de este intercambio.');
            redirigir('intercambios/' . $i['id']);
        }

        $this->vista('intercambio/confirmar-unirse', [
            'titulo'  => 'Unirme a ' . $i['nombre'],
            'i'       => $i,
            'abierto' => Intercambio::registroAbierto($i),
        ]);
    }

    /** POST /unirse/{codigo}  — se une de verdad */
    public function unirse(string $codigo): void
    {
        Auth::requerir();
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

        (new Participante())->unir((int)$i['id'], Auth::id());
        NotificacionService::nuevoParticipante($i, Auth::id(), Auth::usuario()['nombre']);
        flash('success', '¡Ya estás dentro de "' . $i['nombre'] . '"! Completa tu lista de deseos para ayudar a tu amigo secreto.');
        redirigir('intercambios/' . $i['id']);
    }

    /* ===================== AYUDANTES ===================== */

    /**
     * Carga el intercambio y verifica que el usuario sea admin o participante.
     * Si no, responde 404 (no revelamos que el intercambio existe).
     */
    private function cargarConAcceso(int $id): array
    {
        $i = (new Intercambio())->buscarPorId($id);
        $esMiembro = $i && ((int)$i['admin_id'] === Auth::id()
                            || (new Participante())->buscar($id, Auth::id()));
        if (!$esMiembro) {
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

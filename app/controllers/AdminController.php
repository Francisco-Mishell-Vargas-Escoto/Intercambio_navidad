<?php
/**
 * Archivo: app/controllers/AdminController.php
 * Panel de administración del organizador.
 * TODAS las acciones verifican primero que el usuario sea el admin del intercambio.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';
require_once RUTA_APP . '/core/Validator.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/models/Participante.php';
require_once RUTA_APP . '/models/Usuario.php';
require_once RUTA_APP . '/models/Deseo.php';
require_once RUTA_APP . '/models/Invitacion.php';
require_once RUTA_APP . '/models/Restriccion.php';
require_once RUTA_APP . '/models/Asignacion.php';
require_once RUTA_APP . '/services/CodigoService.php';
require_once RUTA_APP . '/services/UploadService.php';
require_once RUTA_APP . '/services/IntercambioValidacion.php';
require_once RUTA_APP . '/controllers/IntercambioController.php';

class AdminController extends Controller
{
    /* ===================== PANEL ===================== */

    /** GET /intercambios/{id}/admin */
    public function panel(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);

        require_once RUTA_APP . '/core/Participacion.php';
        $this->vista('admin/panel', [
            'titulo'        => 'Administrar · ' . $i['nombre'],
            'i'             => $i,
            'stats'         => (new Intercambio())->estadisticas((int)$i['id']),
            'participantes' => (new Participante())->listarDetalle((int)$i['id']),
            'invitaciones'  => (new Invitacion())->listar((int)$i['id']),
            'enlace'        => IntercambioController::enlaceInvitacion($i['codigo_acceso']),
            'bloqueado'     => $this->bloqueadoPorSorteo($i),
            'restricciones' => (new Restriccion())->listar((int)$i['id']),
            'asignaciones'  => (new Asignacion())->contar((int)$i['id']),      // solo el número
            'vistas'        => (new Asignacion())->contarVistas((int)$i['id']),
        ]);
    }

    /* ===================== CONFIGURACIÓN ===================== */

    /** POST /intercambios/{id}/admin/configuracion  — editar datos y presupuesto */
    public function guardarConfiguracion(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();

        $ruta = 'intercambios/' . $i['id'] . '/admin';
        list($datos, $v) = IntercambioValidacion::validar($_POST);
        if ($v->falla()) {
            $this->regresarConErrores($ruta . '#configuracion', $v->errores(), IntercambioValidacion::CAMPOS);
        }

        (new Intercambio())->actualizar((int)$i['id'], $datos);
        flash('success', 'Configuración guardada.');
        redirigir($ruta . '#configuracion');
    }

    /** POST /intercambios/{id}/admin/registro  — cerrar o reabrir el registro */
    public function cambiarRegistro(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $modelo = new Intercambio();
        $ruta = 'intercambios/' . $i['id'] . '/admin';

        if (($_POST['accion'] ?? '') === 'cerrar') {
            $ok = $modelo->cambiarEstado((int)$i['id'], 'abierto', 'cerrado');
            flash($ok ? 'success' : 'warning', $ok ? 'Registro cerrado. Ya nadie puede unirse con el código.'
                                                  : 'El registro no estaba abierto.');
        } else {
            if (strtotime($i['fecha_limite']) < time()) {
                flash('warning', 'La fecha límite ya pasó. Cámbiala en Configuración para reabrir el registro.');
                redirigir($ruta . '#configuracion');
            }
            $ok = $modelo->cambiarEstado((int)$i['id'], 'cerrado', 'abierto');
            flash($ok ? 'success' : 'warning', $ok ? 'Registro reabierto.' : 'El registro no se puede reabrir en este momento.');
        }
        redirigir($ruta);
    }

    /** POST /intercambios/{id}/admin/codigo  — genera un código nuevo (el anterior deja de funcionar) */
    public function regenerarCodigo(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $codigo = CodigoService::generarUnico(Database::conectar());
        (new Intercambio())->cambiarCodigo((int)$i['id'], $codigo);
        flash('success', 'Nuevo código: ' . CodigoService::formatear($codigo) . '. El código y el enlace anteriores ya no funcionan.');
        redirigir('intercambios/' . $i['id'] . '/admin#invitaciones');
    }

    /* ===================== PARTICIPANTES ===================== */

    /**
     * POST /intercambios/{id}/admin/participantes
     * El organizador agrega a alguien con su nombre (y correo opcional). No necesita cuenta:
     * se crea su participación con un enlace personal que el organizador le comparte.
     * Si el correo pertenece a una cuenta registrada, la participación queda vinculada a ella.
     */
    public function agregarParticipante(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $ruta = 'intercambios/' . $i['id'] . '/admin#participantes';

        if ($this->bloqueadoPorSorteo($i)) {
            flash('warning', 'El sorteo ya se realizó. Reinícialo para poder agregar participantes.');
            redirigir($ruta);
        }

        $v = new Validator($_POST);
        $v->requerido('nombre', 'El nombre')->min('nombre', 2, 'El nombre')->max('nombre', 120, 'El nombre')
          ->email('email')->max('email', 150, 'El correo');
        $partModelo = new Participante();
        if (!$v->falla() && $partModelo->existeNombre((int)$i['id'], $v->valor('nombre'))) {
            $v->error('nombre', 'Ya hay un participante con ese nombre.');
        }

        $usuario = $v->valor('email') !== '' ? (new Usuario())->buscarPorEmail($v->valor('email')) : null;
        if (!$v->falla() && $usuario && $partModelo->buscarPorUsuario((int)$i['id'], (int)$usuario['id'])) {
            $v->error('email', 'La cuenta con ese correo ya participa en este intercambio.');
        }
        if ($v->falla()) {
            $this->regresarConErrores($ruta, $v->errores(), ['nombre', 'email']);
        }

        $p = $partModelo->crear((int)$i['id'], $v->valor('nombre'), $v->valor('email') ?: null,
                                $usuario ? (int)$usuario['id'] : null);
        NotificacionService::nuevoParticipante($i, $p, true);
        flash('success', 'Agregaste a ' . $p['nombre'] . '. Envíale su enlace personal con los botones de la tabla.');
        redirigir($ruta);
    }

    /** POST /intercambios/{id}/admin/participantes/{pid}/enlace  — genera un enlace personal nuevo */
    public function regenerarEnlace(string $id, string $pid): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $p = $this->cargarParticipante($i, (int)$pid);
        (new Participante())->regenerarToken((int)$p['id']);
        flash('success', 'Nuevo enlace personal para ' . $p['nombre'] . '. El anterior ya no funciona; envíale el nuevo.');
        redirigir('intercambios/' . $i['id'] . '/admin#participantes');
    }

    /** GET /intercambios/{id}/admin/participantes/{pid} */
    public function editarParticipante(string $id, string $pid): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        $p = $this->cargarParticipante($i, (int)$pid);

        $this->vista('admin/editar-participante', [
            'titulo' => 'Editar participante',
            'i'      => $i,
            'p'      => $p,
            'deseos' => (new Deseo())->listar((int)$p['id']),
        ]);
    }

    /** POST /intercambios/{id}/admin/participantes/{pid} */
    public function actualizarParticipante(string $id, string $pid): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $p = $this->cargarParticipante($i, (int)$pid);

        $v = new Validator($_POST);
        $v->requerido('nombre', 'El nombre')->min('nombre', 2, 'El nombre')->max('nombre', 120, 'El nombre');
        foreach (['gustos', 'intereses', 'no_le_gusta', 'preferencias', 'comentarios'] as $campo) {
            $v->max($campo, 1000, 'Este campo');
        }
        if (!$v->falla() && (new Participante())->existeNombre((int)$i['id'], $v->valor('nombre'), (int)$p['id'])) {
            $v->error('nombre', 'Ya hay otro participante con ese nombre.');
        }
        $ruta = 'intercambios/' . $i['id'] . '/admin/participantes/' . $p['id'];
        if ($v->falla()) {
            $this->regresarConErrores($ruta, $v->errores(), ['nombre', 'gustos', 'intereses', 'no_le_gusta', 'preferencias', 'comentarios']);
        }

        $modelo = new Participante();
        $modelo->actualizarPerfil((int)$p['id'], [
            'nombre'       => $v->valor('nombre'),
            'gustos'       => $v->valor('gustos') ?: null,
            'intereses'    => $v->valor('intereses') ?: null,
            'no_le_gusta'  => $v->valor('no_le_gusta') ?: null,
            'preferencias' => $v->valor('preferencias') ?: null,
            'comentarios'  => $v->valor('comentarios') ?: null,
        ]);
        if (!$this->bloqueadoPorSorteo($i)) {
            $modelo->confirmar((int)$p['id'], !empty($_POST['confirmado']));
        }

        flash('success', 'Información de ' . $p['nombre'] . ' actualizada.');
        redirigir('intercambios/' . $i['id'] . '/admin#participantes');
    }

    /** POST /intercambios/{id}/admin/participantes/{pid}/eliminar */
    public function eliminarParticipante(string $id, string $pid): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $p = $this->cargarParticipante($i, (int)$pid);
        $ruta = 'intercambios/' . $i['id'] . '/admin#participantes';

        if ($this->bloqueadoPorSorteo($i)) {
            flash('warning', 'El sorteo ya se realizó. Reinícialo para poder eliminar participantes.');
            redirigir($ruta);
        }

        // Borramos las fotos de sus deseos antes de que la BD elimine los registros
        foreach ((new Deseo())->listar((int)$p['id']) as $d) {
            UploadService::borrar($d['imagen']);
        }
        (new Participante())->eliminar((int)$p['id']);

        flash('info', 'Eliminaste a ' . $p['nombre'] . ' del intercambio.');
        redirigir($ruta);
    }

    /* ===================== INVITACIONES ===================== */

    /** POST /intercambios/{id}/admin/invitaciones  — correo opcional */
    public function crearInvitacion(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $ruta = 'intercambios/' . $i['id'] . '/admin#invitaciones';

        $v = new Validator($_POST);
        $v->email('email')->max('email', 150, 'El correo');
        if ($v->falla()) {
            $this->regresarConErrores($ruta, $v->errores(), ['email']);
        }

        $this->crearInvitacionPara($i, $v->valor('email') ?: null);
        redirigir($ruta);
    }

    /** POST /intercambios/{id}/admin/invitaciones/{inv}/eliminar */
    public function eliminarInvitacion(string $id, string $inv): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        (new Invitacion())->eliminar((int)$inv, (int)$i['id']);   // solo borra si es de este intercambio
        flash('info', 'Invitación eliminada: su enlace ya no funciona.');
        redirigir('intercambios/' . $i['id'] . '/admin#invitaciones');
    }

    /* ===================== AYUDANTES ===================== */

    /** Crea una invitación (o reutiliza la pendiente de ese correo) y avisa con un mensaje. */
    private function crearInvitacionPara(array $i, ?string $email): void
    {
        $modelo = new Invitacion();
        if ($email && $modelo->pendientePorEmail((int)$i['id'], $email)) {
            flash('info', 'Ya existe una invitación pendiente para ' . $email . '. Puedes reenviarla desde la lista.');
            return;
        }
        $modelo->crear((int)$i['id'], $email);
        flash('success', $email
            ? $email . ' todavía no tiene cuenta: se creó una invitación personal. Envíasela desde la lista de invitaciones.'
            : 'Se creó una invitación. Copia su enlace y compártelo.');
    }

    /** Solo el organizador pasa; cualquier otra persona recibe un 404. */
    private function cargarComoAdmin(int $id): array
    {
        Auth::requerir();
        $i = (new Intercambio())->buscarPorId($id);
        if (!$i || (int)$i['admin_id'] !== Auth::id()) {
            $this->noEncontrado();
        }
        return $i;
    }

    /** El participante debe pertenecer a ESTE intercambio. */
    private function cargarParticipante(array $i, int $pid): array
    {
        $p = (new Participante())->buscarEnIntercambio($pid, (int)$i['id']);
        if (!$p) {
            $this->noEncontrado();
        }
        return $p;
    }

    /** Tras el sorteo no se puede cambiar la lista de participantes (rompería las asignaciones). */
    private function bloqueadoPorSorteo(array $i): bool
    {
        return in_array($i['estado'], ['sorteado', 'revelado'], true);
    }

    private function noEncontrado(): void
    {
        http_response_code(404);
        $this->vista('errors/404', ['titulo' => 'No encontrado']);
        exit;
    }
}

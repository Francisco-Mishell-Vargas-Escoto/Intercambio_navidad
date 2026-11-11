<?php
/**
 * Archivo: app/controllers/InvitacionController.php
 * Aceptar una invitación personal: /invitacion/{token}
 * A diferencia del código general, una invitación personal funciona aunque
 * ya haya pasado la fecha límite (el organizador la envió a propósito).
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/models/Participante.php';
require_once RUTA_APP . '/models/Invitacion.php';

class InvitacionController extends Controller
{
    /** GET /invitacion/{token} */
    public function ver(string $token): void
    {
        Auth::requerir();   // tras iniciar sesión o registrarse regresa a este enlace
        list($inv, $i) = $this->cargar($token);

        if ((new Participante())->buscar((int)$i['id'], Auth::id())) {
            flash('info', 'Ya formas parte de este intercambio.');
            redirigir('intercambios/' . $i['id']);
        }

        $this->vista('intercambio/confirmar-invitacion', [
            'titulo' => 'Invitación a ' . $i['nombre'],
            'i'      => $i,
            'inv'    => $inv,
            'error'  => $this->problema($inv, $i),
        ]);
    }

    /** POST /invitacion/{token} */
    public function aceptar(string $token): void
    {
        Auth::requerir();
        Csrf::verificar();
        list($inv, $i) = $this->cargar($token);

        $problema = $this->problema($inv, $i);
        if ($problema) {
            flash('warning', $problema);
            redirigir('invitacion/' . $token);
        }

        (new Participante())->unir((int)$i['id'], Auth::id());
        (new Invitacion())->marcarAceptada((int)$inv['id']);
        NotificacionService::nuevoParticipante($i, Auth::id(), Auth::usuario()['nombre']);
        flash('success', '¡Aceptaste la invitación a "' . $i['nombre'] . '"! Completa tu lista de deseos.');
        redirigir('intercambios/' . $i['id']);
    }

    /* ===================== AYUDANTES ===================== */

    private function cargar(string $token): array
    {
        $inv = preg_match('/^[a-f0-9]{64}$/', $token) ? (new Invitacion())->buscarPorToken($token) : null;
        $i = $inv ? (new Intercambio())->buscarPorId((int)$inv['intercambio_id']) : null;
        if (!$inv || !$i) {
            flash('error', 'La invitación no existe o fue cancelada por el organizador.');
            redirigir('mis-intercambios');
        }
        return [$inv, $i];
    }

    /** Devuelve el motivo por el que NO se puede aceptar (o null si todo está bien). */
    private function problema(array $inv, array $i): ?string
    {
        if ($inv['aceptada_en']) {
            return 'Esta invitación ya se usó.';
        }
        if (in_array($i['estado'], ['sorteado', 'revelado'], true)) {
            return 'El sorteo ya se realizó, así que no es posible unirse. Habla con el organizador.';
        }
        // Si la invitación es para un correo concreto, solo esa cuenta puede usarla
        if ($inv['email'] && $inv['email'] !== mb_strtolower(Auth::usuario()['email'])) {
            return 'Esta invitación es para ' . $inv['email'] . '. Inicia sesión (o regístrate) con ese correo para aceptarla.';
        }
        return null;
    }
}

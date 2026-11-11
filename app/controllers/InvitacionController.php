<?php
/**
 * Archivo: app/controllers/InvitacionController.php
 * Aceptar una invitación personal: /invitacion/{token}
 * No necesita cuenta: la persona escribe su nombre y queda dentro.
 * A diferencia del código general, una invitación personal funciona aunque
 * ya haya pasado la fecha límite (el organizador la envió a propósito).
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';
require_once RUTA_APP . '/core/Participacion.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/models/Invitacion.php';
require_once RUTA_APP . '/controllers/IntercambioController.php';

class InvitacionController extends Controller
{
    /** GET /invitacion/{token} */
    public function ver(string $token): void
    {
        list($inv, $i) = $this->cargar($token);

        if (Participacion::actual((int)$i['id'])) {
            flash('info', 'Ya formas parte de este intercambio.');
            redirigir('intercambios/' . $i['id']);
        }

        $this->vista('intercambio/confirmar-unirse', [
            'titulo'   => 'Invitación a ' . $i['nombre'],
            'i'        => $i,
            'abierto'  => $this->problema($inv, $i) === null,
            'problema' => $this->problema($inv, $i),
            'accion'   => url('invitacion/' . $inv['token']),
            'personal' => true,
        ]);
    }

    /** POST /invitacion/{token} */
    public function aceptar(string $token): void
    {
        Csrf::verificar();
        list($inv, $i) = $this->cargar($token);

        $problema = $this->problema($inv, $i);
        if ($problema) {
            flash('warning', $problema);
            redirigir('invitacion/' . $token);
        }

        IntercambioController::registrarParticipante($i, 'invitacion/' . $token, $inv['email']);
        (new Invitacion())->marcarAceptada((int)$inv['id']);
        flash('success', '¡Aceptaste la invitación a "' . $i['nombre'] . '"! Guarda tu enlace personal y completa tu lista de deseos.');
        redirigir('intercambios/' . $i['id']);
    }

    /* ===================== AYUDANTES ===================== */

    private function cargar(string $token): array
    {
        $inv = preg_match('/^[a-f0-9]{64}$/', $token) ? (new Invitacion())->buscarPorToken($token) : null;
        $i = $inv ? (new Intercambio())->buscarPorId((int)$inv['intercambio_id']) : null;
        if (!$inv || !$i) {
            flash('error', 'La invitación no existe o fue cancelada por el organizador.');
            redirigir('/');
        }
        return [$inv, $i];
    }

    /** Devuelve el motivo por el que NO se puede aceptar (o null si todo está bien). */
    private function problema(array $inv, array $i): ?string
    {
        if ($inv['aceptada_en']) {
            return 'Esta invitación ya se usó. Si eres tú, entra con tu enlace personal (el organizador puede reenviártelo).';
        }
        if (in_array($i['estado'], ['sorteado', 'revelado'], true)) {
            return 'El sorteo ya se realizó, así que no es posible unirse. Habla con el organizador.';
        }
        return null;
    }
}

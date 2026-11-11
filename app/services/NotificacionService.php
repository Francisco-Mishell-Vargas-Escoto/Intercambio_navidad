<?php
/**
 * Archivo: app/services/NotificacionService.php
 * Mensajes de cada evento del intercambio. Los controladores solo llaman
 * NotificacionService::algo(...) y aquí decidimos a quién avisar y qué decir.
 *
 * Si algún día configuras correo (PHPMailer + SMTP), este es el único archivo
 * que habría que tocar para enviar también un email.
 */

require_once RUTA_APP . '/models/Notificacion.php';

class NotificacionService
{
    /**
     * Alguien se unió (con código, invitación o agregado por el admin).
     * $p = fila del participante. Solo los participantes con cuenta reciben avisos en la campanita.
     */
    public static function nuevoParticipante(array $i, array $p, bool $loAgregoElAdmin = false): void
    {
        $n = new Notificacion();
        $usuarioId = $p['usuario_id'] ? (int)$p['usuario_id'] : 0;

        if ($usuarioId && $usuarioId !== (int)$i['admin_id']) {
            $n->crear($usuarioId, (int)$i['id'], 'registro', $loAgregoElAdmin
                ? $i['admin_nombre'] . ' te agregó al intercambio "' . $i['nombre'] . '". ¡Completa tu lista de deseos!'
                : 'Te uniste a "' . $i['nombre'] . '". ¡Completa tu lista de deseos!');
        }
        // Al organizador le avisamos cuando alguien se une por su cuenta (no cuando él lo agregó)
        if (!$loAgregoElAdmin && $usuarioId !== (int)$i['admin_id']) {
            $n->crear((int)$i['admin_id'], (int)$i['id'], 'registro', $p['nombre'] . ' se unió a "' . $i['nombre'] . '".');
        }
    }

    /** Un participante confirmó o canceló su asistencia: avisamos al organizador. */
    public static function confirmacion(array $i, array $p, bool $confirmo): void
    {
        if ((int)$p['usuario_id'] === (int)$i['admin_id']) return;   // el organizador no se avisa a sí mismo
        (new Notificacion())->crear((int)$i['admin_id'], (int)$i['id'], 'confirmacion',
            $p['nombre'] . ($confirmo ? ' confirmó su participación en "' : ' canceló su confirmación en "') . $i['nombre'] . '".');
    }

    public static function sorteoRealizado(array $i): void
    {
        (new Notificacion())->crearParaParticipantes((int)$i['id'], 'sorteo',
            '¡Ya se hizo el sorteo de "' . $i['nombre'] . '"! Te avisaremos cuando puedas descubrir a tu amigo secreto.',
            (int)$i['admin_id']);
    }

    public static function sorteoReiniciado(array $i): void
    {
        (new Notificacion())->crearParaParticipantes((int)$i['id'], 'sorteo',
            'El organizador reinició el sorteo de "' . $i['nombre'] . '". Habrá un nuevo sorteo pronto.',
            (int)$i['admin_id']);
    }

    /** Resultados publicados: avisamos a TODOS los participantes (incluido el admin si participa). */
    public static function resultadosPublicados(array $i): void
    {
        (new Notificacion())->crearParaParticipantes((int)$i['id'], 'revelacion',
            '🎁 ¡Ya puedes descubrir a tu amigo secreto de "' . $i['nombre'] . '"!');
    }

    /** A dónde lleva cada notificación al hacer clic. */
    public static function enlace(array $n): string
    {
        if (!$n['intercambio_id']) {
            return url('mis-intercambios');
        }
        if ($n['tipo'] === 'revelacion') {
            return url('intercambios/' . (int)$n['intercambio_id'] . '/mi-amigo-secreto');
        }
        return url('intercambios/' . (int)$n['intercambio_id']);
    }

    /** Ícono de Bootstrap Icons para cada tipo. */
    public static function icono(string $tipo): string
    {
        $iconos = [
            'registro'     => 'bi-person-plus',
            'confirmacion' => 'bi-check2-circle',
            'sorteo'       => 'bi-shuffle',
            'revelacion'   => 'bi-gift',
            'invitacion'   => 'bi-envelope-heart',
        ];
        return $iconos[$tipo] ?? 'bi-bell';
    }
}

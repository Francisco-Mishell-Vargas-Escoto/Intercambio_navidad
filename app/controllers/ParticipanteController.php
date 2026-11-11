<?php
/**
 * Archivo: app/controllers/ParticipanteController.php
 * Perfil del participante dentro de un intercambio, foto y confirmación.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';
require_once RUTA_APP . '/core/Validator.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/models/Participante.php';
require_once RUTA_APP . '/models/Deseo.php';
require_once RUTA_APP . '/models/Usuario.php';
require_once RUTA_APP . '/services/UploadService.php';

class ParticipanteController extends Controller
{
    /** GET /intercambios/{id}/perfil */
    public function perfil(string $id): void
    {
        Auth::requerir();
        list($i, $p) = $this->cargar((int)$id);

        $this->vista('participante/perfil', [
            'titulo' => 'Mi perfil · ' . $i['nombre'],
            'i'      => $i,
            'p'      => $p,
            'deseos' => (new Deseo())->listar((int)$p['id']),
            'yo'     => Auth::usuario(),
        ]);
    }

    /** POST /intercambios/{id}/perfil */
    public function guardarPerfil(string $id): void
    {
        Auth::requerir();
        Csrf::verificar();
        list($i, $p) = $this->cargar((int)$id);

        $v = new Validator($_POST);
        foreach (['gustos' => 'Tus gustos', 'intereses' => 'Tus intereses', 'no_le_gusta' => 'Lo que no te gusta',
                  'preferencias' => 'Tus preferencias', 'comentarios' => 'Los comentarios'] as $campo => $etiqueta) {
            $v->max($campo, 1000, $etiqueta);
        }

        // Foto de perfil (opcional): se valida antes de guardar nada
        $subida = UploadService::imagen($_FILES['foto'] ?? null, 'fotos');
        if ($subida['error']) {
            $v->error('foto', $subida['error']);
        }

        $ruta = 'intercambios/' . $i['id'] . '/perfil';
        if ($v->falla()) {
            UploadService::borrar($subida['archivo']);   // no dejamos fotos huérfanas
            $this->regresarConErrores($ruta, $v->errores(), ['gustos', 'intereses', 'no_le_gusta', 'preferencias', 'comentarios']);
        }

        (new Participante())->actualizarPerfil((int)$p['id'], [
            'gustos'       => $v->valor('gustos') ?: null,
            'intereses'    => $v->valor('intereses') ?: null,
            'no_le_gusta'  => $v->valor('no_le_gusta') ?: null,
            'preferencias' => $v->valor('preferencias') ?: null,
            'comentarios'  => $v->valor('comentarios') ?: null,
        ]);

        if ($subida['archivo']) {
            $this->cambiarFoto($subida['archivo']);
        } elseif (!empty($_POST['quitar_foto'])) {
            $this->cambiarFoto(null);
        }

        flash('success', 'Tu perfil se guardó. ¡Tu amigo secreto te lo agradecerá!');
        redirigir($ruta);
    }

    /** POST /intercambios/{id}/confirmar  — confirma o cancela la participación */
    public function confirmar(string $id): void
    {
        Auth::requerir();
        Csrf::verificar();
        list($i, $p) = $this->cargar((int)$id);

        // Después del sorteo ya no se puede cambiar (las asignaciones dependen de ello)
        if (in_array($i['estado'], ['sorteado', 'revelado'], true)) {
            flash('warning', 'El sorteo ya se realizó; tu participación ya no se puede cambiar.');
            redirigir('intercambios/' . $i['id']);
        }

        $confirmar = !empty($_POST['confirmado']);
        (new Participante())->confirmar((int)$p['id'], $confirmar);
        NotificacionService::confirmacion($i, Auth::id(), Auth::usuario()['nombre'], $confirmar);
        flash($confirmar ? 'success' : 'info',
              $confirmar ? '¡Confirmaste tu participación! Nos vemos el ' . fecha_corta($i['fecha_evento']) . '.'
                         : 'Quitaste tu confirmación. Puedes volver a confirmar cuando quieras.');

        $volver = ($_POST['volver'] ?? '') === 'perfil' ? '/perfil' : '';
        redirigir('intercambios/' . $i['id'] . $volver);
    }

    /* ===================== AYUDANTES ===================== */

    /** Cambia la foto del usuario, borra la anterior y actualiza la sesión. */
    private function cambiarFoto(?string $nueva): void
    {
        $anterior = Auth::usuario()['foto'];
        (new Usuario())->actualizarFoto(Auth::id(), $nueva);
        UploadService::borrar($anterior);
        $_SESSION['usuario']['foto'] = $nueva;
    }

    /**
     * Devuelve [intercambio, participante] del usuario en sesión.
     * Si no participa en ese intercambio, responde 404.
     */
    private function cargar(int $intercambioId): array
    {
        $i = (new Intercambio())->buscarPorId($intercambioId);
        $p = $i ? (new Participante())->buscar($intercambioId, Auth::id()) : null;
        if (!$p) {
            http_response_code(404);
            $this->vista('errors/404', ['titulo' => 'No encontrado']);
            exit;
        }
        return [$i, $p];
    }
}

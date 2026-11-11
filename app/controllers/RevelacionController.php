<?php
/**
 * Archivo: app/controllers/RevelacionController.php
 * "Mi amigo secreto": cada participante ve SOLO a quién le regala, con su perfil y su lista de deseos.
 *
 * Privacidad:
 *  - La asignación se busca siempre con el participante actual (su enlace personal o su cuenta),
 *    nunca con un ID que venga en la URL.
 *  - Solo funciona cuando el organizador ya publicó los resultados (estado "revelado").
 *  - La página se envía con "no-store" para que el navegador no la guarde en caché
 *    (útil en computadoras compartidas).
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/core/Participacion.php';
require_once RUTA_APP . '/models/Asignacion.php';
require_once RUTA_APP . '/models/Deseo.php';

class RevelacionController extends Controller
{
    /** GET /intercambios/{id}/mi-amigo-secreto */
    public function ver(string $id): void
    {
        list($i, $yo, $asignacion) = $this->cargar((int)$id);

        // El receptor siempre se busca DENTRO del mismo intercambio
        $receptor = (new Participante())->buscarEnIntercambio((int)$asignacion['receptor_id'], (int)$i['id']);

        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex');

        $this->vista('revelacion/ver', [
            'titulo'     => 'Mi amigo secreto',
            'i'          => $i,
            'asignacion' => $asignacion,
            'receptor'   => $receptor,
            'deseos'     => (new Deseo())->listar((int)$receptor['id']),
            'yaLoVio'    => $asignacion['visto_en'] !== null,
        ]);
    }

    /** POST /intercambios/{id}/mi-amigo-secreto/visto  (lo llama el JavaScript al abrir el regalo) */
    public function marcarVisto(string $id): void
    {
        Csrf::verificar();   // el token viaja en el encabezado X-CSRF-Token
        list(, , $asignacion) = $this->cargar((int)$id);
        (new Asignacion())->marcarVista((int)$asignacion['id']);
        $this->json(['ok' => true]);
    }

    /* ===================== AYUDANTES ===================== */

    /** Devuelve [intercambio, mi participación, mi asignación] o redirige con un mensaje. */
    private function cargar(int $intercambioId): array
    {
        $i = (new Intercambio())->buscarPorId($intercambioId);
        $yo = $i ? Participacion::actual($intercambioId) : null;   // enlace personal o cuenta
        if (!$yo) {
            http_response_code(404);
            $this->vista('errors/404', ['titulo' => 'No encontrado']);
            exit;
        }

        if ($i['estado'] !== 'revelado') {
            flash('info', $i['estado'] === 'sorteado'
                ? 'El sorteo ya se hizo, pero el organizador aún no publica los resultados. ¡Un poco de paciencia!'
                : 'El sorteo todavía no se realiza.');
            redirigir('intercambios/' . $i['id']);
        }

        $asignacion = (new Asignacion())->deDador($intercambioId, (int)$yo['id']);
        if (!$asignacion) {
            flash('warning', 'No tienes una asignación en este intercambio. Habla con el organizador.');
            redirigir('intercambios/' . $i['id']);
        }
        return [$i, $yo, $asignacion];
    }
}

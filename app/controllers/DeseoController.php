<?php
/**
 * Archivo: app/controllers/DeseoController.php
 * Agregar, editar y eliminar elementos de la lista de deseos.
 * Todas las acciones verifican que el deseo sea del usuario en sesión.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/core/Validator.php';
require_once RUTA_APP . '/core/Participacion.php';
require_once RUTA_APP . '/models/Deseo.php';
require_once RUTA_APP . '/services/UploadService.php';

class DeseoController extends Controller
{
    const MAX_DESEOS = 15;   // límite razonable por persona

    /** POST /intercambios/{id}/deseos  — agrega un deseo */
    public function crear(string $id): void
    {
        Csrf::verificar();

        $p = Participacion::actual((int)$id);
        if (!$p) {
            $this->noEncontrado();
        }
        $ruta = 'intercambios/' . (int)$id . '/perfil';

        $modelo = new Deseo();
        if ($modelo->contar((int)$p['id']) >= self::MAX_DESEOS) {
            flash('warning', 'Llegaste al máximo de ' . self::MAX_DESEOS . ' deseos. Elimina alguno para agregar otro.');
            redirigir($ruta . '#lista-deseos');
        }

        list($datos, $v) = $this->validar(null);
        if ($v->falla()) {
            $this->regresarConErrores($ruta . '#nuevo-deseo', $v->errores(), ['titulo', 'descripcion', 'enlace', 'prioridad']);
        }

        $modelo->crear((int)$p['id'], $datos);
        flash('success', 'Agregaste "' . $datos['titulo'] . '" a tu lista de deseos.');
        redirigir($ruta . '#lista-deseos');
    }

    /** GET /deseos/{id}/editar */
    public function editar(string $id): void
    {
        $d = $this->cargar((int)$id);
        $this->vista('participante/editar-deseo', ['titulo' => 'Editar deseo', 'd' => $d]);
    }

    /** POST /deseos/{id}/editar */
    public function actualizar(string $id): void
    {
        Csrf::verificar();
        $d = $this->cargar((int)$id);

        list($datos, $v) = $this->validar($d);
        if ($v->falla()) {
            $this->regresarConErrores('deseos/' . $d['id'] . '/editar', $v->errores(), ['titulo', 'descripcion', 'enlace', 'prioridad']);
        }

        (new Deseo())->actualizar((int)$d['id'], $datos);
        if ($datos['imagen'] !== $d['imagen']) {
            UploadService::borrar($d['imagen']);     // se cambió o quitó la foto anterior
        }
        flash('success', 'Deseo actualizado.');
        redirigir('intercambios/' . $d['intercambio_id'] . '/perfil#lista-deseos');
    }

    /** POST /deseos/{id}/eliminar */
    public function eliminar(string $id): void
    {
        Csrf::verificar();
        $d = $this->cargar((int)$id);

        (new Deseo())->eliminar((int)$d['id']);
        UploadService::borrar($d['imagen']);
        flash('info', 'Eliminaste "' . $d['titulo'] . '" de tu lista.');
        redirigir('intercambios/' . $d['intercambio_id'] . '/perfil#lista-deseos');
    }

    /* ===================== AYUDANTES ===================== */

    /**
     * Valida el formulario de deseo. $actual = deseo existente al editar (o null).
     * Devuelve [datos limpios, validador].
     */
    private function validar(?array $actual): array
    {
        $v = new Validator($_POST);
        $v->requerido('titulo', 'La idea de regalo')->max('titulo', 150, 'La idea de regalo')
          ->max('descripcion', 1000, 'La descripción')
          ->max('enlace', 500, 'El enlace');

        // El enlace debe ser una dirección web real (http o https)
        $enlace = $v->valor('enlace');
        if ($enlace !== '' && (!filter_var($enlace, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $enlace))) {
            $v->error('enlace', 'Escribe un enlace completo, que empiece con https://');
        }

        $prioridad = (int)($_POST['prioridad'] ?? 2);
        if (!in_array($prioridad, [1, 2, 3], true)) {
            $prioridad = 2;
        }

        // Imagen: nueva, conservar la actual o quitarla
        $imagen = $actual ? $actual['imagen'] : null;
        if (!$v->falla()) {
            $subida = UploadService::imagen($_FILES['imagen'] ?? null, 'deseos');
            if ($subida['error']) {
                $v->error('imagen', $subida['error']);
            } elseif ($subida['archivo']) {
                $imagen = $subida['archivo'];
            } elseif (!empty($_POST['quitar_imagen'])) {
                $imagen = null;
            }
        }

        return [[
            'titulo'      => $v->valor('titulo'),
            'descripcion' => $v->valor('descripcion') ?: null,
            'enlace'      => $enlace ?: null,
            'imagen'      => $imagen,
            'prioridad'   => $prioridad,
        ], $v];
    }

    /** Carga el deseo solo si es del participante actual; si no, 404. */
    private function cargar(int $deseoId): array
    {
        $modelo = new Deseo();
        $p = Participacion::actual($modelo->intercambioDe($deseoId));
        $d = $p ? $modelo->buscarDeParticipante($deseoId, (int)$p['id']) : null;
        if (!$d) {
            $this->noEncontrado();
        }
        return $d;
    }

    private function noEncontrado(): void
    {
        http_response_code(404);
        $this->vista('errors/404', ['titulo' => 'No encontrado']);
        exit;
    }
}

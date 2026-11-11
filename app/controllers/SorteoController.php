<?php
/**
 * Archivo: app/controllers/SorteoController.php
 * Restricciones, realizar sorteo, reiniciarlo y publicar resultados.
 * Solo el organizador puede usar estas acciones.
 *
 * Protecciones contra sortear varias veces:
 *  1. Ventana de confirmación + botón que se desactiva al primer clic (app.js).
 *  2. Transacción con SELECT ... FOR UPDATE: la fila del intercambio queda bloqueada.
 *  3. Solo se sortea si el estado es "abierto" o "cerrado"; al terminar pasa a "sorteado".
 *  4. Los índices UNIQUE de la tabla asignaciones impiden filas duplicadas.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/models/Participante.php';
require_once RUTA_APP . '/models/Restriccion.php';
require_once RUTA_APP . '/models/Asignacion.php';
require_once RUTA_APP . '/services/SorteoService.php';

class SorteoController extends Controller
{
    const MIN_PARTICIPANTES = 3;

    /* ===================== RESTRICCIONES ===================== */

    /** POST /intercambios/{id}/admin/restricciones */
    public function agregarRestriccion(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $volver = 'intercambios/' . $i['id'] . '/admin#sorteo';

        if ($this->yaSorteado($i)) {
            flash('warning', 'Reinicia el sorteo para poder cambiar las restricciones.');
            redirigir($volver);
        }

        $a = (int)($_POST['dador_id'] ?? 0);
        $b = (int)($_POST['receptor_id'] ?? 0);
        $bidireccional = !empty($_POST['bidireccional']);
        $pModelo = new Participante();

        // Ambos deben ser participantes de ESTE intercambio y distintos
        if (!$pModelo->buscarEnIntercambio($a, (int)$i['id']) || !$pModelo->buscarEnIntercambio($b, (int)$i['id'])) {
            flash('error', 'Elige dos participantes del intercambio.');
            redirigir($volver);
        }
        if ($a === $b) {
            flash('error', 'Elige dos personas distintas (nadie se regala a sí mismo de todas formas).');
            redirigir($volver);
        }

        $rModelo = new Restriccion();
        if ($rModelo->existe((int)$i['id'], $a, $b)) {
            flash('info', 'Esa restricción ya existe.');
            redirigir($volver);
        }

        // ¿Con esta restricción el sorteo seguiría siendo posible? Lo probamos antes de guardar.
        $ids = array_column($pModelo->listarDetalle((int)$i['id']), 'id');
        if (count($ids) >= self::MIN_PARTICIPANTES) {
            $pares = $rModelo->pares((int)$i['id']);
            $pares[] = [$a, $b];
            if ($bidireccional) $pares[] = [$b, $a];
            if ((new SorteoService())->asignar($ids, $pares) === null) {
                flash('error', 'Con esa restricción el sorteo sería imposible con los participantes actuales. No se guardó.');
                redirigir($volver);
            }
        }

        $rModelo->crear((int)$i['id'], $a, $b, $bidireccional);
        flash('success', 'Restricción agregada.');
        redirigir($volver);
    }

    /** POST /intercambios/{id}/admin/restricciones/{rid}/eliminar */
    public function eliminarRestriccion(string $id, string $rid): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        if ($this->yaSorteado($i)) {
            flash('warning', 'Reinicia el sorteo para poder cambiar las restricciones.');
        } else {
            (new Restriccion())->eliminar((int)$rid, (int)$i['id']);
            flash('info', 'Restricción eliminada.');
        }
        redirigir('intercambios/' . $i['id'] . '/admin#sorteo');
    }

    /* ===================== SORTEO ===================== */

    /** POST /intercambios/{id}/admin/sorteo */
    public function realizar(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $volver = 'intercambios/' . $i['id'] . '/admin#sorteo';

        $db = Database::conectar();
        $db->beginTransaction();
        try {
            // Bloquea la fila: nadie más puede sortear este intercambio hasta el commit
            $estado = (new Intercambio())->estadoBloqueado((int)$i['id']);
            if (!in_array($estado, ['abierto', 'cerrado'], true)) {
                $db->rollBack();
                flash('warning', 'El sorteo ya se había realizado. No se hizo nada.');
                redirigir($volver);
            }

            $ids = array_map('intval', array_column((new Participante())->listarDetalle((int)$i['id']), 'id'));
            if (count($ids) < self::MIN_PARTICIPANTES) {
                $db->rollBack();
                flash('error', 'Se necesitan al menos ' . self::MIN_PARTICIPANTES . ' participantes para el sorteo.');
                redirigir($volver);
            }

            $pares = (new Restriccion())->pares((int)$i['id']);
            $resultado = (new SorteoService())->asignar($ids, $pares);

            // Doble verificación antes de guardar
            if ($resultado === null || !SorteoService::esValido($ids, $resultado, $pares)) {
                $db->rollBack();
                flash('error', 'No existe una asignación posible con las restricciones actuales. Elimina alguna e inténtalo de nuevo.');
                redirigir($volver);
            }

            (new Asignacion())->guardarTodas((int)$i['id'], $resultado);
            (new Intercambio())->registrarSorteo((int)$i['id'], 'sorteado');
            $db->commit();
            NotificacionService::sorteoRealizado($i);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        flash('success', '¡Sorteo realizado! ' . count($resultado) . ' asignaciones guardadas en secreto. '
                       . 'Revisa que todo esté bien y publica los resultados cuando quieras.');
        redirigir($volver);
    }

    /** POST /intercambios/{id}/admin/sorteo/reiniciar  — solo ANTES de publicar */
    public function reiniciar(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();
        $volver = 'intercambios/' . $i['id'] . '/admin#sorteo';

        $db = Database::conectar();
        $db->beginTransaction();
        try {
            $estado = (new Intercambio())->estadoBloqueado((int)$i['id']);
            if ($estado !== 'sorteado') {
                $db->rollBack();
                flash('warning', $estado === 'revelado'
                    ? 'Los resultados ya se publicaron: el sorteo ya no se puede reiniciar.'
                    : 'No hay un sorteo que reiniciar.');
                redirigir($volver);
            }
            (new Asignacion())->borrarTodas((int)$i['id']);
            (new Intercambio())->registrarSorteo((int)$i['id'], 'cerrado');   // registro queda cerrado
            $db->commit();
            NotificacionService::sorteoReiniciado($i);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }

        flash('info', 'Sorteo reiniciado. Las asignaciones se borraron; el registro quedó cerrado (puedes reabrirlo en Resumen).');
        redirigir($volver);
    }

    /** POST /intercambios/{id}/admin/sorteo/publicar  — es DEFINITIVO */
    public function publicar(string $id): void
    {
        $i = $this->cargarComoAdmin((int)$id);
        Csrf::verificar();

        $ok = (new Intercambio())->cambiarEstado((int)$i['id'], 'sorteado', 'revelado');
        if ($ok) {
            (new Intercambio())->registrarSorteo((int)$i['id'], 'revelado');   // guarda la fecha de publicación
            NotificacionService::resultadosPublicados($i);
            flash('success', '¡Resultados publicados! Cada participante ya puede descubrir a su amigo secreto.');
        } else {
            flash('warning', 'Primero realiza el sorteo (o los resultados ya estaban publicados).');
        }
        redirigir('intercambios/' . $i['id'] . '/admin#sorteo');
    }

    /* ===================== AYUDANTES ===================== */

    private function yaSorteado(array $i): bool
    {
        return in_array($i['estado'], ['sorteado', 'revelado'], true);
    }

    private function cargarComoAdmin(int $id): array
    {
        Auth::requerir();
        $i = (new Intercambio())->buscarPorId($id);
        if (!$i || (int)$i['admin_id'] !== Auth::id()) {
            http_response_code(404);
            $this->vista('errors/404', ['titulo' => 'No encontrado']);
            exit;
        }
        return $i;
    }
}

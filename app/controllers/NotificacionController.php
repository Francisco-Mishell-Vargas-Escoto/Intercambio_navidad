<?php
/**
 * Archivo: app/controllers/NotificacionController.php
 * Lista de notificaciones, abrir una y marcar todas como leídas.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/services/NotificacionService.php';

class NotificacionController extends Controller
{
    /** GET /notificaciones */
    public function index(): void
    {
        Auth::requerir();
        $this->vista('notificaciones/index', [
            'titulo'         => 'Notificaciones',
            'notificaciones' => (new Notificacion())->listar(Auth::id(), 100),
        ]);
    }

    /** GET /notificaciones/{id}  — la marca como leída y lleva a su destino */
    public function abrir(string $id): void
    {
        Auth::requerir();
        $modelo = new Notificacion();
        $n = $modelo->buscarDeUsuario((int)$id, Auth::id());   // solo las propias
        if (!$n) {
            redirigir('notificaciones');
        }
        $modelo->marcarLeida((int)$n['id'], Auth::id());
        header('Location: ' . NotificacionService::enlace($n));
        exit;
    }

    /** POST /notificaciones/leer-todas */
    public function leerTodas(): void
    {
        Auth::requerir();
        Csrf::verificar();
        (new Notificacion())->marcarTodas(Auth::id());
        flash('info', 'Todas las notificaciones quedaron como leídas.');
        redirigir('notificaciones');
    }
}

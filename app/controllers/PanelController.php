<?php
/**
 * Archivo: app/controllers/PanelController.php
 * "Mis intercambios": página privada del usuario.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/models/Intercambio.php';

class PanelController extends Controller
{
    /** GET /mis-intercambios */
    public function index(): void
    {
        Auth::requerir();   // solo usuarios con sesión

        $this->vista('panel/index', [
            'titulo'       => 'Mis intercambios',
            'usuario'      => Auth::usuario(),
            'intercambios' => (new Intercambio())->listarDeUsuario(Auth::id()),
        ]);
    }
}

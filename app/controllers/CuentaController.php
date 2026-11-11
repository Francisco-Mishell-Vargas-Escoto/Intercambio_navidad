<?php
/**
 * Archivo: app/controllers/CuentaController.php
 * "Mi cuenta": el organizador cambia el nombre que ven los demás.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/core/Validator.php';
require_once RUTA_APP . '/models/Usuario.php';

class CuentaController extends Controller
{
    /** GET /mi-cuenta */
    public function ver(): void
    {
        Auth::requerir();
        $this->vista('cuenta/index', ['titulo' => 'Mi cuenta', 'yo' => Auth::usuario()]);
    }

    /** POST /mi-cuenta */
    public function guardar(): void
    {
        Auth::requerir();
        Csrf::verificar();

        $v = new Validator($_POST);
        $v->requerido('nombre', 'El nombre')->min('nombre', 3, 'El nombre')->max('nombre', 120, 'El nombre');
        if ($v->falla()) {
            $this->regresarConErrores('mi-cuenta', $v->errores(), ['nombre']);
        }

        $nombre = preg_replace('/\s+/u', ' ', $v->valor('nombre'));   // quita espacios dobles
        (new Usuario())->actualizarNombre(Auth::id(), $nombre);
        $_SESSION['usuario']['nombre'] = $nombre;                       // el menú cambia al instante

        flash('success', 'Listo: ahora apareces como ' . $nombre . '.');
        redirigir('mi-cuenta');
    }
}

<?php
/**
 * Archivo: app/core/Controller.php
 * Clase base de la que heredan todos los controladores.
 */

class Controller
{
    /**
     * Muestra una vista dentro del layout principal.
     * $this->vista('home/index', ['titulo' => 'Inicio']);
     * Cada clave del arreglo se convierte en una variable dentro de la vista.
     */
    public function vista(string $nombre, array $datos = [], string $layout = 'main'): void
    {
        extract($datos);                          // ['titulo'=>'x'] => $titulo = 'x'

        // 1) Generamos el contenido de la vista y lo guardamos en $contenido
        ob_start();
        require RUTA_VISTAS . '/' . $nombre . '.php';
        $contenido = ob_get_clean();

        // 2) Lo insertamos dentro del layout (encabezado, menú, nieve, pie)
        require RUTA_VISTAS . '/layouts/' . $layout . '.php';
    }

    /**
     * Regresa al formulario con los errores y lo que el usuario escribió.
     * $camposARecordar: solo esos campos se guardan (nunca contraseñas).
     */
    protected function regresarConErrores(string $ruta, array $errores, array $camposARecordar): void
    {
        $_SESSION['errores'] = $errores;
        $_SESSION['old'] = array_intersect_key($_POST, array_flip($camposARecordar));
        redirigir($ruta);
    }

    /** Responde en formato JSON (lo usaremos con fetch en JavaScript). */
    public function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

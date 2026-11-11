<?php
/**
 * Archivo: app/core/Router.php
 * El Router decide qué controlador y método atienden cada URL.
 *
 * Ejemplo de registro:
 *   $router->get('/intercambio/{id}', 'IntercambioController@ver');
 * Si el usuario visita /intercambio/5 se ejecuta IntercambioController->ver('5')
 */

class Router
{
    // Lista de rutas: ['GET' => [ '/ruta' => 'Controlador@metodo' ], 'POST' => [...]]
    private $rutas = ['GET' => [], 'POST' => []];

    public function get(string $patron, string $accion): void
    {
        $this->rutas['GET'][$patron] = $accion;
    }

    public function post(string $patron, string $accion): void
    {
        $this->rutas['POST'][$patron] = $accion;
    }

    /** Busca la ruta que coincide con la URL solicitada y la ejecuta. */
    public function despachar(string $metodo, string $uri): void
    {
        // Quitamos la carpeta base y los parámetros ?x=y
        $ruta = parse_url($uri, PHP_URL_PATH);
        if (strpos($ruta, BASE_URL) === 0) {   // ¿la ruta empieza con la carpeta base?
            $ruta = substr($ruta, strlen(BASE_URL));
        }
        $ruta = '/' . trim($ruta, '/');

        foreach ($this->rutas[$metodo] ?? [] as $patron => $accion) {
            // Convertimos {id} en una expresión regular que capture el valor
            $regex = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([a-zA-Z0-9_-]+)', $patron) . '$#';

            if (preg_match($regex, $ruta, $coincidencias)) {
                array_shift($coincidencias);                 // quitamos la coincidencia completa
                [$controlador, $funcion] = explode('@', $accion);

                $archivo = RUTA_APP . '/controllers/' . $controlador . '.php';
                require_once $archivo;
                $objeto = new $controlador();
                $objeto->$funcion(...$coincidencias);        // pasamos los parámetros de la URL
                return;
            }
        }

        // Si ninguna ruta coincidió: página 404
        http_response_code(404);
        require_once RUTA_APP . '/core/Controller.php';
        (new Controller())->vista('errors/404', ['titulo' => 'Página no encontrada']);
    }
}

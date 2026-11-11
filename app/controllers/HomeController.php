<?php
/**
 * Archivo: app/controllers/HomeController.php
 * Página de inicio y prueba de conexión.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/models/Intercambio.php';
require_once RUTA_APP . '/core/Participacion.php';

class HomeController extends Controller
{
    /** GET /  → página principal */
    public function index(): void
    {
        $modelo = new Intercambio();
        $proximo = Auth::check() ? $modelo->proximoDeUsuario(Auth::id()) : null;

        // Intercambios en los que participa ESTE navegador (enlace personal guardado), con o sin cuenta
        $enDispositivo = [];
        foreach (Participacion::delDispositivo() as $p) {
            $i = $modelo->buscarPorId((int)$p['intercambio_id']);
            if ($i) {
                $enDispositivo[] = $i;
                if (!$proximo && strtotime($i['fecha_evento']) >= time()) {
                    $proximo = $i;   // sin cuenta: el próximo intercambio de este navegador
                }
            }
        }

        $this->vista('home/index', [
            'titulo'        => 'Inicio',
            'proximo'       => $proximo,
            'enDispositivo' => $enDispositivo,
        ]);
    }

    /** GET /faq → preguntas frecuentes (página pública) */
    public function faq(): void
    {
        $this->vista('faq/index', ['titulo' => 'Preguntas frecuentes']);
    }

    /** GET /prueba-conexion → verifica que PHP habla con MySQL */
    public function pruebaConexion(): void
    {
        $pdo = Database::conectar();

        // Contamos las tablas que existen en nuestra base de datos
        $consulta = $pdo->prepare(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME'
        );
        $consulta->execute([DB_NOMBRE]);
        $tablas = $consulta->fetchAll(PDO::FETCH_COLUMN);

        $this->vista('home/prueba', [
            'titulo'  => 'Prueba de conexión',
            'tablas'  => $tablas,
            'version' => $pdo->query('SELECT VERSION()')->fetchColumn(),
        ]);
    }
}

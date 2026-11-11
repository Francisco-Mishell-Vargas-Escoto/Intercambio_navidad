<?php
/**
 * Archivo: app/controllers/HomeController.php
 * Página de inicio y prueba de conexión.
 */

require_once RUTA_APP . '/core/Controller.php';
require_once RUTA_APP . '/models/Intercambio.php';

class HomeController extends Controller
{
    /** GET /  → página principal */
    public function index(): void
    {
        // Si hay sesión, mostramos el próximo intercambio real del usuario
        $proximo = Auth::check() ? (new Intercambio())->proximoDeUsuario(Auth::id()) : null;

        $this->vista('home/index', [
            'titulo'  => 'Inicio',
            'proximo' => $proximo,
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

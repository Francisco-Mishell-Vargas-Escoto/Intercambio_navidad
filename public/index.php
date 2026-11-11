<?php
/**
 * Archivo: public/index.php
 * PUNTO DE ENTRADA ÚNICO. Todas las peticiones pasan por aquí
 * gracias a los archivos .htaccess.
 */

// 1) Configuración y clases base
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once RUTA_APP . '/core/helpers.php';
require_once RUTA_APP . '/core/Router.php';
require_once RUTA_APP . '/core/Auth.php';
require_once RUTA_APP . '/core/Csrf.php';
require_once RUTA_APP . '/core/Migraciones.php';

// 2) Sesión segura (la cookie no es accesible desde JavaScript)
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();
Auth::revisarInactividad(120);   // cierra la sesión tras 2 horas sin actividad
Migraciones::ejecutar(Database::conectar());   // aplica cambios pendientes de la base de datos (normalmente no hace nada)

// 3) Definición de rutas (se irán agregando en cada módulo)
$router = new Router();

// Módulo 1: inicio
$router->get('/', 'HomeController@index');
$router->get('/prueba-conexion', 'HomeController@pruebaConexion');

// Módulo 2: autenticación
$router->get('/registro', 'AuthController@mostrarRegistro');
$router->post('/registro', 'AuthController@registrar');
$router->get('/login', 'AuthController@mostrarLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/mis-intercambios', 'PanelController@index');

// Módulo 3: intercambios
$router->get('/intercambios/crear', 'IntercambioController@crear');
$router->post('/intercambios/crear', 'IntercambioController@guardar');
$router->get('/intercambios/{id}', 'IntercambioController@ver');
$router->get('/unirse', 'IntercambioController@formularioUnirse');
$router->post('/unirse', 'IntercambioController@buscarCodigo');
$router->get('/unirse/{codigo}', 'IntercambioController@confirmarUnirse');
$router->post('/unirse/{codigo}', 'IntercambioController@unirse');
$router->get('/p/{token}', 'IntercambioController@enlacePersonal');          // enlace personal del participante
$router->post('/intercambios/{id}/salir', 'IntercambioController@olvidarDispositivo');
$router->get('/entrar', 'IntercambioController@formularioEntrar');            // entrar con el código personal
$router->post('/entrar', 'IntercambioController@entrar');

// Módulo 4: perfil, confirmación y lista de deseos
$router->get('/intercambios/{id}/perfil', 'ParticipanteController@perfil');
$router->post('/intercambios/{id}/perfil', 'ParticipanteController@guardarPerfil');
$router->post('/intercambios/{id}/confirmar', 'ParticipanteController@confirmar');
$router->post('/intercambios/{id}/deseos', 'DeseoController@crear');
$router->get('/deseos/{id}/editar', 'DeseoController@editar');
$router->post('/deseos/{id}/editar', 'DeseoController@actualizar');
$router->post('/deseos/{id}/eliminar', 'DeseoController@eliminar');

// Módulo 5: panel de administración e invitaciones
$router->get('/intercambios/{id}/admin', 'AdminController@panel');
$router->post('/intercambios/{id}/admin/configuracion', 'AdminController@guardarConfiguracion');
$router->post('/intercambios/{id}/admin/registro', 'AdminController@cambiarRegistro');
$router->post('/intercambios/{id}/admin/codigo', 'AdminController@regenerarCodigo');
$router->post('/intercambios/{id}/admin/eliminar', 'AdminController@eliminarIntercambio');
$router->post('/intercambios/{id}/admin/participantes', 'AdminController@agregarParticipante');
$router->get('/intercambios/{id}/admin/participantes/{pid}', 'AdminController@editarParticipante');
$router->post('/intercambios/{id}/admin/participantes/{pid}', 'AdminController@actualizarParticipante');
$router->post('/intercambios/{id}/admin/participantes/{pid}/eliminar', 'AdminController@eliminarParticipante');
$router->post('/intercambios/{id}/admin/participantes/{pid}/enlace', 'AdminController@regenerarEnlace');
$router->post('/intercambios/{id}/admin/invitaciones', 'AdminController@crearInvitacion');
$router->post('/intercambios/{id}/admin/invitaciones/{inv}/eliminar', 'AdminController@eliminarInvitacion');
$router->get('/invitacion/{token}', 'InvitacionController@ver');
$router->post('/invitacion/{token}', 'InvitacionController@aceptar');

// Módulo 6: restricciones y sorteo
$router->post('/intercambios/{id}/admin/restricciones', 'SorteoController@agregarRestriccion');
$router->post('/intercambios/{id}/admin/restricciones/{rid}/eliminar', 'SorteoController@eliminarRestriccion');
$router->post('/intercambios/{id}/admin/sorteo', 'SorteoController@realizar');
$router->post('/intercambios/{id}/admin/sorteo/reiniciar', 'SorteoController@reiniciar');
$router->post('/intercambios/{id}/admin/sorteo/publicar', 'SorteoController@publicar');

// Módulo 7: revelación del amigo secreto
$router->get('/intercambios/{id}/mi-amigo-secreto', 'RevelacionController@ver');
$router->post('/intercambios/{id}/mi-amigo-secreto/visto', 'RevelacionController@marcarVisto');

// Módulo 8: notificaciones y preguntas frecuentes
$router->get('/faq', 'HomeController@faq');
$router->get('/notificaciones', 'NotificacionController@index');
$router->post('/notificaciones/leer-todas', 'NotificacionController@leerTodas');
$router->get('/notificaciones/{id}', 'NotificacionController@abrir');


// 4) Ejecutar la ruta que corresponde a la URL actual
$router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

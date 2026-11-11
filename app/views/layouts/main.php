<?php
/**
 * Archivo: app/views/layouts/main.php
 * Plantilla común: encabezado, menú, nieve, mensajes flash y pie de página.
 * La variable $contenido trae el HTML de la vista que se está mostrando.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? '') ?> · <?= e(APP_NOMBRE) ?></title>

    <!-- Bootstrap 5 y Bootstrap Icons (requieren internet) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Tipografías: Poppins (texto) y Mountains of Christmas (títulos festivos) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Mountains+of+Christmas:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Nuestros estilos -->
    <link href="<?= asset('css/estilos.css') ?>" rel="stylesheet">
</head>
<body>

<!-- Lienzo donde cae la nieve (nieve.js) -->
<canvas id="nieve" aria-hidden="true"></canvas>

<!-- ===================== MENÚ ===================== -->
<nav class="navbar navbar-expand-lg navbar-navidad sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= url('/') ?>">
            <i class="bi bi-gift-fill text-dorado"></i> <?= e(APP_NOMBRE) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('faq') ?>">Preguntas frecuentes</a></li>
                <?php if (Auth::check()):
                    $yo = Auth::usuario();
                    // Campanita: número de no leídas y las 5 más recientes
                    require_once RUTA_APP . '/services/NotificacionService.php';
                    $notifModelo = new Notificacion();
                    $noLeidas = $notifModelo->contarNoLeidas(Auth::id());
                    $recientes = $notifModelo->listar(Auth::id(), 5);
                ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('mis-intercambios') ?>">Mis intercambios</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative campanita" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false" aria-label="Notificaciones<?= $noLeidas ? " ($noLeidas sin leer)" : '' ?>">
                            <i class="bi bi-bell<?= $noLeidas ? '-fill' : '' ?>"></i>
                            <span class="d-lg-none">Notificaciones</span>
                            <?php if ($noLeidas): ?>
                                <span class="badge rounded-pill bg-dorado contador-notif"><?= $noLeidas > 9 ? '9+' : $noLeidas ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end menu-notif p-0">
                            <div class="px-3 py-2 border-bottom fw-medium small">Notificaciones</div>
                            <?php if (!$recientes): ?>
                                <p class="small text-muted px-3 py-3 mb-0">No tienes notificaciones todavía.</p>
                            <?php endif; ?>
                            <?php foreach ($recientes as $n): ?>
                                <a class="dropdown-item notif <?= $n['leida'] ? '' : 'no-leida' ?>" href="<?= url('notificaciones/' . (int)$n['id']) ?>">
                                    <i class="bi <?= NotificacionService::icono($n['tipo']) ?>"></i>
                                    <span><?= e($n['mensaje']) ?><small class="d-block text-muted"><?= e(fecha_hora($n['creado_en'])) ?></small></span>
                                </a>
                            <?php endforeach; ?>
                            <a class="dropdown-item text-center small text-pino border-top py-2" href="<?= url('notificaciones') ?>">Ver todas</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= e(explode(' ', $yo['nombre'])[0]) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted"><?= e($yo['email']) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <!-- Cerrar sesión es un formulario POST con token CSRF -->
                                <form method="post" action="<?= url('logout') ?>" class="m-0">
                                    <?= Csrf::campo() ?>
                                    <button type="submit" class="dropdown-item text-navidad">
                                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login') ?>">Iniciar sesión</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-dorado" href="<?= url('registro') ?>">Crear cuenta</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="contenido-principal">
    <!-- Mensajes flash (éxito, error, aviso) -->
    <div class="container mt-3">
        <?php foreach (flash() as $f): ?>
            <div class="alert alert-<?= $f['tipo'] === 'error' ? 'danger' : e($f['tipo']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['mensaje']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endforeach; ?>
    </div>

    <?= $contenido ?>
</main>

<!-- ===================== PIE ===================== -->
<footer class="pie-navidad mt-5">
    <div class="container text-center py-4">
        <p class="mb-1 mensaje-navideno"><i class="bi bi-stars text-dorado"></i> <?= e(mensaje_navideno()) ?></p>
        <small class="opacity-75">&copy; <?= date('Y') ?> <?= e(APP_NOMBRE) ?></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/nieve.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

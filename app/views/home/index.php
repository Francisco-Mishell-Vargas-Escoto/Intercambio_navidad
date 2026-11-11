<?php
/**
 * Archivo: app/views/home/index.php
 * Página de inicio.
 */
?>
<!-- ===== HERO ===== -->
<section class="hero text-center">
    <div class="container">
        <div class="hero-icono mb-3"><i class="bi bi-gift"></i></div>
        <h1 class="titulo-festivo">Intercambio Navideño</h1>
        <p class="lead mb-4">Organiza tu intercambio de regalos en minutos: crea el evento, invita a tus amigos
            y deja que el sorteo haga la magia.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= url('intercambios/crear') ?>" class="btn btn-navidad btn-lg">
                <i class="bi bi-plus-circle"></i> Crear intercambio
            </a>
            <a href="<?= url('unirse') ?>" class="btn btn-pino btn-lg">
                <i class="bi bi-ticket-perforated"></i> Unirme a un intercambio
            </a>
        </div>
    </div>
</section>

<!-- ===== TARJETA DEL INTERCAMBIO =====
     Si el usuario tiene sesión y un intercambio próximo, mostramos sus datos reales;
     si no, una tarjeta de ejemplo. -->
<?php
if ($proximo) {
    $t = [
        'etiqueta' => 'Tu próximo intercambio',
        'nombre'   => $proximo['nombre'],
        'mensaje'  => $proximo['descripcion'] ?: '¡Bienvenidos! Que empiece la magia navideña.',
        'fecha'    => fecha_corta($proximo['fecha_evento']),
        'presu'    => presupuesto($proximo),
        'total'    => (int)$proximo['total_participantes'],
        'js'       => fecha_js($proximo['fecha_evento']),
        'enlace'   => url('intercambios/' . (int)$proximo['id']),
    ];
} else {
    $t = [
        'etiqueta' => 'Ejemplo',
        'nombre'   => 'Intercambio Amigos ' . date('Y'),
        'mensaje'  => '¡Bienvenidos! Que empiece la magia navideña.',
        'fecha'    => '20 dic ' . date('Y'),
        'presu'    => '$300.00 – $500.00',
        'total'    => 12,
        'js'       => date('Y') . '-12-20T19:00:00',
        'enlace'   => null,
    ];
}
?>
<section class="container mt-n5 position-relative">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card tarjeta-intercambio">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <span class="badge bg-dorado mb-2"><?= e($t['etiqueta']) ?></span>
                            <h2 class="h4 mb-1"><?= e($t['nombre']) ?></h2>
                            <p class="text-muted mb-0"><?= e($t['mensaje']) ?></p>
                        </div>
                        <i class="bi bi-tree-fill icono-arbol"></i>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-6 col-md-4">
                            <div class="dato"><i class="bi bi-calendar-event"></i>
                                <small>Fecha</small><strong><?= e($t['fecha']) ?></strong></div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="dato"><i class="bi bi-cash-coin"></i>
                                <small>Presupuesto</small><strong><?= e($t['presu']) ?></strong></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="dato"><i class="bi bi-people"></i>
                                <small>Participantes</small><strong><?= $t['total'] ?></strong></div>
                        </div>
                    </div>

                    <!-- Cuenta regresiva: app.js lee data-fecha y la actualiza cada segundo -->
                    <div class="cuenta-regresiva mt-4" data-fecha="<?= e($t['js']) ?>">
                        <div><span data-unidad="dias">0</span><small>días</small></div>
                        <div><span data-unidad="horas">0</span><small>horas</small></div>
                        <div><span data-unidad="minutos">0</span><small>min</small></div>
                        <div><span data-unidad="segundos">0</span><small>seg</small></div>
                    </div>

                    <?php if ($t['enlace']): ?>
                        <div class="text-center mt-4">
                            <a href="<?= $t['enlace'] ?>" class="btn btn-pino">Ver intercambio <i class="bi bi-arrow-right"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== INTERCAMBIOS DE ESTE DISPOSITIVO (participantes sin cuenta) ===== -->
<?php if (!Auth::check() && $enDispositivo): ?>
<section class="container mt-5">
    <h2 class="h5 text-center mb-3 titulo-seccion">Tus intercambios en este dispositivo</h2>
    <div class="row g-3 justify-content-center">
        <?php foreach ($enDispositivo as $d): list($eTexto, $eColor) = estado_intercambio($d); ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= url('intercambios/' . (int)$d['id']) ?>" class="card tarjeta-intercambio h-100 text-decoration-none text-reset">
                    <div class="card-body p-3">
                        <span class="badge text-bg-<?= $eColor ?> mb-1"><?= e($eTexto) ?></span>
                        <h3 class="h6 mb-1"><?= e($d['nombre']) ?></h3>
                        <p class="small text-muted mb-0"><i class="bi bi-calendar-event"></i> <?= e(fecha_hora($d['fecha_evento'])) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== MENSAJE NAVIDEÑO ALEATORIO ===== -->
<section class="container mt-5">
    <div class="mensaje-del-dia text-center mx-auto">
        <i class="bi bi-snow text-dorado"></i>
        <p class="mb-2" id="mensaje-dia" aria-live="polite"><?= e(mensaje_navideno()) ?></p>
        <!-- app.js toma la lista de data-mensajes y muestra otro al azar -->
        <button type="button" class="btn btn-sm btn-link text-pino" id="otro-mensaje"
                data-mensajes="<?= e(json_encode(mensajes_navidenos(), JSON_UNESCAPED_UNICODE)) ?>">
            <i class="bi bi-shuffle"></i> Otro mensaje
        </button>
    </div>
</section>

<!-- ===== CÓMO FUNCIONA ===== -->
<section class="container mt-5">
    <h2 class="text-center mb-4 titulo-seccion">¿Cómo funciona?</h2>
    <div class="row g-4">
        <?php
        $pasos = [
            ['bi-pencil-square', 'Crea el intercambio', 'Define fecha, presupuesto, lugar y reglas.'],
            ['bi-envelope-heart', 'Invita a tus amigos', 'Comparte el código o el enlace de invitación.'],
            ['bi-shuffle', 'Haz el sorteo', 'Asignaciones aleatorias, justas y secretas.'],
            ['bi-box2-heart', 'Descubre y regala', 'Revela a tu amigo secreto y mira su lista de deseos.'],
        ];
        foreach ($pasos as $n => [$icono, $tituloPaso, $texto]): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card tarjeta-paso h-100 text-center">
                    <div class="card-body">
                        <div class="paso-numero"><?= $n + 1 ?></div>
                        <i class="bi <?= $icono ?> paso-icono"></i>
                        <h3 class="h6 mt-2"><?= e($tituloPaso) ?></h3>
                        <p class="small text-muted mb-0"><?= e($texto) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

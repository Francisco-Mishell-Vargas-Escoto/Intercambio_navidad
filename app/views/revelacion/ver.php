<?php
/**
 * Archivo: app/views/revelacion/ver.php
 * Revelación del amigo secreto: regalo animado → nombre → perfil y lista de deseos.
 * Variables: $i, $asignacion, $receptor, $deseos, $yaLoVio
 */
$prioridades = [1 => ['Alta', 'danger'], 2 => ['Media', 'warning'], 3 => ['Baja', 'secondary']];
$perfil = [
    ['gustos', 'bi-heart', 'Le gusta'],
    ['intereses', 'bi-controller', 'Intereses y hobbies'],
    ['preferencias', 'bi-rulers', 'Preferencias'],
    ['comentarios', 'bi-chat-heart', 'Comentarios'],
];
?>
<!-- ===== Escenario del regalo ===== -->
<section class="escenario-revelacion <?= $yaLoVio ? 'abierto' : '' ?>" id="escenario"
         data-url-visto="<?= url('intercambios/' . (int)$i['id'] . '/mi-amigo-secreto/visto') ?>"
         data-csrf="<?= Csrf::token() ?>" data-ya-visto="<?= $yaLoVio ? '1' : '0' ?>">
    <canvas id="confeti" aria-hidden="true"></canvas>
    <div class="container text-center position-relative">
        <p class="text-muted small mb-1"><?= e($i['nombre']) ?></p>

        <!-- Antes de abrir -->
        <div class="antes-de-abrir">
            <h1 class="titulo-festivo mb-4">Tu amigo secreto te espera</h1>
            <button type="button" class="regalo" id="regalo" aria-label="Abrir el regalo para descubrir a tu amigo secreto">
                <span class="regalo-tapa"><span class="regalo-mono"></span></span>
                <span class="regalo-caja"></span>
            </button>
            <p class="mt-4 mb-0 text-muted"><i class="bi bi-hand-index"></i> Toca el regalo para abrirlo</p>
            <p class="small text-muted">Asegúrate de que nadie esté viendo tu pantalla 🤫</p>
        </div>

        <!-- Después de abrir -->
        <div class="despues-de-abrir" aria-live="polite">
            <p class="mb-2 text-pino fw-medium">Este año le regalas a…</p>
            <?php if ($receptor['foto']): ?>
                <img src="<?= url('uploads/' . e($receptor['foto'])) ?>" alt="" class="avatar avatar-xl mb-3">
            <?php else: ?>
                <span class="avatar avatar-xl mb-3"><?= e(iniciales($receptor['nombre'])) ?></span>
            <?php endif; ?>
            <h1 class="titulo-festivo nombre-receptor mb-2" tabindex="-1"><?= e($receptor['nombre']) ?></h1>
            <p class="text-muted mb-1">Presupuesto: <strong><?= e(presupuesto($i)) ?></strong> ·
                Entrega: <strong><?= e(fecha_hora($i['fecha_evento'])) ?></strong></p>
            <p class="small text-muted mb-3">Es un secreto: no le digas a nadie hasta el día del intercambio.</p>
            <?php if ($yaLoVio): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="repetir">
                    <i class="bi bi-arrow-repeat"></i> Ver la animación otra vez</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== Perfil y lista de deseos del receptor (se muestra al abrir) ===== -->
<section class="container mt-4 contenido-receptor">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card tarjeta-intercambio h-100">
                <div class="card-body p-4">
                    <h2 class="h5 seccion-form"><span><i class="bi bi-person"></i></span> Pistas sobre <?= e(explode(' ', $receptor['nombre'])[0]) ?></h2>
                    <?php $hayPistas = false; ?>
                    <?php foreach ($perfil as list($campo, $icono, $etiqueta)): if (!$receptor[$campo]) continue; $hayPistas = true; ?>
                        <div class="pista">
                            <i class="bi <?= $icono ?>"></i>
                            <div><small><?= e($etiqueta) ?></small><p class="mb-0"><?= nl2br(e($receptor[$campo])) ?></p></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($receptor['no_le_gusta']): $hayPistas = true; ?>
                        <div class="pista pista-no">
                            <i class="bi bi-x-octagon"></i>
                            <div><small>Mejor NO le regales</small><p class="mb-0"><?= nl2br(e($receptor['no_le_gusta'])) ?></p></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!$hayPistas): ?>
                        <p class="text-muted small mb-0">Todavía no llena su perfil. Revisa más tarde: los cambios aparecerán aquí.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card tarjeta-intercambio h-100">
                <div class="card-body p-4">
                    <h2 class="h5 seccion-form"><span><i class="bi bi-gift"></i></span> Su lista de deseos (<?= count($deseos) ?>)</h2>
                    <?php if (!$deseos): ?>
                        <p class="text-muted small mb-0">Aún no agrega ideas de regalo. ¡Usa las pistas de su perfil o vuelve después!</p>
                    <?php else: ?>
                        <div class="d-grid gap-3">
                            <?php foreach ($deseos as $d): list($pTexto, $pColor) = $prioridades[(int)$d['prioridad']]; ?>
                                <div class="deseo">
                                    <?php if ($d['imagen']): ?>
                                        <a href="<?= url('uploads/' . e($d['imagen'])) ?>" target="_blank" rel="noopener">
                                            <img src="<?= url('uploads/' . e($d['imagen'])) ?>" alt="Foto de referencia" class="deseo-img"></a>
                                    <?php else: ?>
                                        <div class="deseo-img deseo-img-vacia"><i class="bi bi-gift"></i></div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <h3 class="h6 mb-0"><?= e($d['titulo']) ?></h3>
                                            <span class="badge text-bg-<?= $pColor ?>">Prioridad <?= $pTexto ?></span>
                                        </div>
                                        <?php if ($d['descripcion']): ?><p class="small text-muted mb-1"><?= nl2br(e($d['descripcion'])) ?></p><?php endif; ?>
                                        <?php if ($d['enlace']): ?>
                                            <a href="<?= e($d['enlace']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="small text-pino">
                                                <i class="bi bi-box-arrow-up-right"></i> Ver producto</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="<?= url('intercambios/' . (int)$i['id']) ?>" class="btn btn-pino"><i class="bi bi-arrow-left"></i> Volver al intercambio</a>
    </div>
</section>

<script src="<?= asset('js/revelacion.js') ?>" defer></script>

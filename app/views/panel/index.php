<?php
/**
 * Archivo: app/views/panel/index.php
 * "Mis intercambios": lista de los intercambios que organizo o en los que participo.
 * Variables: $usuario, $intercambios
 */
?>
<section class="container my-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-0">Hola, <?= e($usuario['nombre']) ?> <i class="bi bi-stars text-dorado"></i></h1>
            <p class="text-muted mb-0">Los intercambios que organizas y en los que participas.</p>
        </div>
        <?php if ($intercambios): ?>
            <div class="d-flex gap-2">
                <a href="<?= url('unirse') ?>" class="btn btn-pino"><i class="bi bi-ticket-perforated"></i> Unirme</a>
                <a href="<?= url('intercambios/crear') ?>" class="btn btn-navidad"><i class="bi bi-plus-circle"></i> Crear</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$intercambios): ?>
        <!-- Estado vacío -->
        <div class="card tarjeta-intercambio text-center">
            <div class="card-body py-5">
                <div class="icono-form mb-3"><i class="bi bi-box2-heart"></i></div>
                <h2 class="h5">Organiza tu primer intercambio</h2>
                <p class="text-muted">Crea uno nuevo o únete con el código que te compartieron.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= url('intercambios/crear') ?>" class="btn btn-navidad"><i class="bi bi-plus-circle"></i> Crear intercambio</a>
                    <a href="<?= url('unirse') ?>" class="btn btn-pino"><i class="bi bi-ticket-perforated"></i> Unirme con código</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($intercambios as $i): [$estadoTexto, $estadoColor] = estado_intercambio($i); ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= url('intercambios/' . (int)$i['id']) ?>" class="card tarjeta-intercambio h-100 text-decoration-none text-reset">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="badge text-bg-<?= $estadoColor ?>"><?= e($estadoTexto) ?></span>
                                <?php if ($i['es_admin']): ?><span class="badge bg-dorado">Organizador</span><?php endif; ?>
                                <?php if ($i['es_participante']): ?><span class="badge text-bg-light border">Participante</span><?php endif; ?>
                            </div>
                            <h2 class="h5 mb-3"><?= e($i['nombre']) ?></h2>
                            <ul class="list-unstyled small text-muted mb-0 lista-iconos">
                                <li><i class="bi bi-calendar-event"></i> <?= e(fecha_hora($i['fecha_evento'])) ?></li>
                                <li><i class="bi bi-cash-coin"></i> <?= e(presupuesto($i)) ?></li>
                                <li><i class="bi bi-people"></i> <?= (int)$i['total_participantes'] ?> participantes</li>
                            </ul>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

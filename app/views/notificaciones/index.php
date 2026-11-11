<?php
/**
 * Archivo: app/views/notificaciones/index.php
 * Todas las notificaciones del usuario. Variable: $notificaciones
 */
$hayNoLeidas = false;
foreach ($notificaciones as $n) { if (!$n['leida']) { $hayNoLeidas = true; break; } }
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <h1 class="h3 mb-0"><i class="bi bi-bell text-navidad"></i> Notificaciones</h1>
                <?php if ($hayNoLeidas): ?>
                    <form method="post" action="<?= url('notificaciones/leer-todas') ?>">
                        <?= Csrf::campo() ?>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-check2-all"></i> Marcar todas como leídas</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card tarjeta-intercambio">
                <div class="card-body p-0">
                    <?php if (!$notificaciones): ?>
                        <div class="text-center py-5">
                            <div class="icono-form mb-3"><i class="bi bi-bell-slash"></i></div>
                            <p class="text-muted mb-0">Aquí te avisaremos cuando alguien se una, confirme o cuando se haga el sorteo.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notificaciones as $n): ?>
                                <a href="<?= url('notificaciones/' . (int)$n['id']) ?>"
                                   class="list-group-item list-group-item-action notif <?= $n['leida'] ? '' : 'no-leida' ?>">
                                    <i class="bi <?= NotificacionService::icono($n['tipo']) ?>"></i>
                                    <span class="flex-grow-1"><?= e($n['mensaje']) ?>
                                        <small class="d-block text-muted"><?= e(fecha_hora($n['creado_en'])) ?></small></span>
                                    <?php if (!$n['leida']): ?><span class="punto-nuevo" title="Sin leer"></span><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

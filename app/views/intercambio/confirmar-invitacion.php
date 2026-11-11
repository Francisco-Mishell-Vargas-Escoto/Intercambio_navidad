<?php
/**
 * Archivo: app/views/intercambio/confirmar-invitacion.php
 * Invitación personal: muestra el intercambio y pide aceptar.
 * Variables: $i, $inv, $error (motivo por el que no se puede aceptar, o null)
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icono-form"><i class="bi bi-envelope-open-heart"></i></div>
                        <p class="text-muted small mt-3 mb-1"><?= e($i['admin_nombre']) ?> te envió una invitación personal a</p>
                        <h1 class="h3 mb-2"><?= e($i['nombre']) ?></h1>
                        <?php if ($i['descripcion']): ?><p class="text-muted mb-0"><?= nl2br(e($i['descripcion'])) ?></p><?php endif; ?>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6"><div class="dato"><i class="bi bi-calendar-event"></i>
                            <small>Fecha</small><strong><?= e(fecha_corta($i['fecha_evento'])) ?></strong></div></div>
                        <div class="col-6"><div class="dato"><i class="bi bi-people"></i>
                            <small>Participantes</small><strong><?= (int)$i['total_participantes'] ?></strong></div></div>
                        <div class="col-12"><div class="dato"><i class="bi bi-cash-coin"></i>
                            <small>Presupuesto</small><strong><?= e(presupuesto($i)) ?></strong></div></div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-warning text-center mb-0"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div>
                    <?php else: ?>
                        <form method="post" action="<?= url('invitacion/' . e($inv['token'])) ?>">
                            <?= Csrf::campo() ?>
                            <button type="submit" class="btn btn-navidad w-100 py-2"><i class="bi bi-check2-circle"></i> Aceptar invitación</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

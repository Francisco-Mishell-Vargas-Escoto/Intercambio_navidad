<?php
/**
 * Archivo: app/views/intercambio/confirmar-unirse.php
 * Muestra el intercambio encontrado y pide confirmar antes de unirse.
 * Variables: $i, $abierto
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icono-form"><i class="bi bi-envelope-paper-heart"></i></div>
                        <p class="text-muted small mt-3 mb-1"><?= e($i['admin_nombre']) ?> te invita a</p>
                        <h1 class="h3 mb-2"><?= e($i['nombre']) ?></h1>
                        <?php if ($i['descripcion']): ?>
                            <p class="text-muted mb-0"><?= nl2br(e($i['descripcion'])) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6"><div class="dato"><i class="bi bi-calendar-event"></i>
                            <small>Fecha</small><strong><?= e(fecha_corta($i['fecha_evento'])) ?></strong></div></div>
                        <div class="col-6"><div class="dato"><i class="bi bi-people"></i>
                            <small>Participantes</small><strong><?= (int)$i['total_participantes'] ?></strong></div></div>
                        <div class="col-12"><div class="dato"><i class="bi bi-cash-coin"></i>
                            <small>Presupuesto</small><strong><?= e(presupuesto($i)) ?></strong></div></div>
                    </div>

                    <?php if ($abierto): ?>
                        <form method="post" action="<?= url('unirse/' . e($i['codigo_acceso'])) ?>">
                            <?= Csrf::campo() ?>
                            <button type="submit" class="btn btn-navidad w-100 py-2">
                                <i class="bi bi-check2-circle"></i> Sí, quiero participar
                            </button>
                        </form>
                        <p class="small text-muted text-center mt-3 mb-0">Puedes unirte hasta el <?= e(fecha_hora($i['fecha_limite'])) ?>.</p>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center mb-0">
                            <i class="bi bi-lock"></i> El registro de este intercambio ya está cerrado.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
/**
 * Archivo: app/views/intercambio/confirmar-unirse.php
 * Muestra el intercambio y pide el nombre para unirse. NO requiere cuenta.
 * Se usa con el código general (/unirse/{codigo}) y con invitaciones personales (/invitacion/{token}).
 * Variables: $i, $abierto, $accion (URL del formulario), $personal (bool, opcional), $problema (opcional)
 */
$personal = $personal ?? false;
$problema = $problema ?? null;
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icono-form"><i class="bi <?= $personal ? 'bi-envelope-open-heart' : 'bi-envelope-paper-heart' ?>"></i></div>
                        <p class="text-muted small mt-3 mb-1"><?= e($i['admin_nombre']) ?> te invita<?= $personal ? ' personalmente' : '' ?> a</p>
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

                    <?php if (!$abierto): ?>
                        <div class="alert alert-secondary text-center mb-0">
                            <i class="bi bi-lock"></i> <?= e($problema ?: 'El registro de este intercambio ya está cerrado.') ?>
                        </div>
                    <?php else: ?>
                        <form method="post" action="<?= e($accion) ?>" class="needs-validation" novalidate>
                            <?= Csrf::campo() ?>
                            <?php if (Auth::check()): ?>
                                <p class="text-center small text-muted">Te unirás como <strong><?= e(Auth::usuario()['nombre']) ?></strong>.</p>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Tu nombre *</label>
                                    <input type="text" class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre"
                                           value="<?= old('nombre') ?>" placeholder="Así te verán los demás, ej. Ana López"
                                           required minlength="2" maxlength="120" autocomplete="name" autofocus>
                                    <div class="invalid-feedback"><?= e(error_campo('nombre')) ?: 'Escribe tu nombre.' ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo <span class="text-muted">(opcional)</span></label>
                                    <input type="email" class="form-control<?= clase_error('email') ?>" id="email" name="email"
                                           value="<?= old('email') ?>" placeholder="nombre@correo.com" maxlength="150" autocomplete="email">
                                    <div class="invalid-feedback"><?= e(error_campo('email')) ?: 'Escribe un correo válido o déjalo vacío.' ?></div>
                                    <div class="form-text">Solo lo verá el organizador.</div>
                                </div>
                                <p class="small text-muted"><i class="bi bi-info-circle text-pino"></i>
                                    No necesitas cuenta. Al unirte recibirás un <strong>enlace personal</strong> para volver cuando quieras.</p>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-navidad w-100 py-2">
                                <i class="bi bi-check2-circle"></i> Unirme al intercambio
                            </button>
                        </form>
                        <?php if (!$personal): ?>
                            <p class="small text-muted text-center mt-3 mb-0">Puedes unirte hasta el <?= e(fecha_hora($i['fecha_limite'])) ?>.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

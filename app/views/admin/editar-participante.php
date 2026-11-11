<?php
/**
 * Archivo: app/views/admin/editar-participante.php
 * El organizador edita la información de un participante.
 * Variables: $i, $p, $deseos
 */
$bloqueado = in_array($i['estado'], ['sorteado', 'revelado'], true);
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= url('intercambios/' . (int)$i['id'] . '/admin#participantes') ?>" class="small text-decoration-none text-pino">
                <i class="bi bi-arrow-left"></i> Volver al panel</a>

            <div class="d-flex align-items-center gap-3 mt-2 mb-4">
                <?php if ($p['foto']): ?>
                    <img src="<?= url('uploads/' . e($p['foto'])) ?>" alt="" class="avatar avatar-lg">
                <?php else: ?>
                    <span class="avatar avatar-lg"><?= e(iniciales($p['nombre'])) ?></span>
                <?php endif; ?>
                <div>
                    <h1 class="h4 mb-0"><?= e($p['nombre']) ?></h1>
                    <p class="text-muted mb-0 small"><?= e($p['email'] ?: 'Sin correo') ?> · se unió el <?= e(fecha_corta($p['creado_en'])) ?></p>
                </div>
            </div>

            <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/participantes/' . (int)$p['id']) ?>"
                  class="needs-validation card tarjeta-intercambio tarjeta-form mb-4" novalidate>
                <div class="card-body p-4">
                    <?= Csrf::campo() ?>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="confirmado" name="confirmado" value="1"
                            <?= $p['confirmado'] ? 'checked' : '' ?> <?= $bloqueado ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="confirmado">Participación confirmada</label>
                        <?php if ($bloqueado): ?><div class="form-text">No se puede cambiar después del sorteo.</div><?php endif; ?>
                    </div>

                    <?php
                    $campos = [
                        ['nombre', 'Nombre'],
                        ['gustos', 'Gustos personales'], ['intereses', 'Intereses y hobbies'],
                        ['no_le_gusta', 'Cosas que no le gustaría recibir'], ['preferencias', 'Preferencias'],
                        ['comentarios', 'Comentarios adicionales'],
                    ];
                    foreach ($campos as list($campo, $etiqueta)):
                        $valor = old($campo) !== '' ? old($campo) : e($p[$campo]);
                    ?>
                        <div class="mb-3">
                            <label for="<?= $campo ?>" class="form-label"><?= e($etiqueta) ?></label>
                            <?php if ($campo === 'nombre'): ?>
                                <input type="text" class="form-control<?= clase_error($campo) ?>" id="nombre" name="nombre"
                                       value="<?= $valor ?>" required minlength="2" maxlength="120">
                            <?php else: ?>
                                <textarea class="form-control<?= clase_error($campo) ?>" id="<?= $campo ?>" name="<?= $campo ?>"
                                          rows="2" maxlength="1000"><?= $valor ?></textarea>
                            <?php endif; ?>
                            <div class="invalid-feedback"><?= e(error_campo($campo)) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-pino"><i class="bi bi-save"></i> Guardar cambios</button>
                </div>
            </form>

            <div class="card tarjeta-intercambio">
                <div class="card-body p-4">
                    <h2 class="h6">Lista de deseos (<?= count($deseos) ?>)</h2>
                    <?php if (!$deseos): ?>
                        <p class="small text-muted mb-0">Aún no agrega ideas de regalo.</p>
                    <?php else: ?>
                        <ul class="small mb-0">
                            <?php foreach ($deseos as $d): ?><li><?= e($d['titulo']) ?></li><?php endforeach; ?>
                        </ul>
                        <p class="small text-muted mt-2 mb-0">Solo el participante puede modificar su lista.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
/**
 * Archivo: app/views/participante/perfil.php
 * Perfil del participante: confirmación, foto, gustos y lista de deseos.
 * Variables: $i (intercambio), $p (participante actual), $deseos, $enlacePersonal
 */
$prioridades = [1 => ['Alta', 'danger'], 2 => ['Media', 'warning'], 3 => ['Baja', 'secondary']];
$puedeConfirmar = !in_array($i['estado'], ['sorteado', 'revelado'], true);
?>
<section class="container my-5">
    <a href="<?= url('intercambios/' . (int)$i['id']) ?>" class="small text-decoration-none text-pino">
        <i class="bi bi-arrow-left"></i> <?= e($i['nombre']) ?>
    </a>
    <h1 class="h3 mt-2 mb-1">Mi perfil y lista de deseos</h1>
    <p class="text-muted">Esta información la verá <strong>solo</strong> la persona que te regale, sin que tú sepas quién es.</p>

    <!-- ===== Confirmación ===== -->
    <div class="card tarjeta-intercambio tarjeta-form mb-4 <?= $p['confirmado'] ? 'tarjeta-confirmada' : '' ?>">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icono-form flex-shrink-0">
                    <i class="bi <?= $p['confirmado'] ? 'bi-check2-circle' : 'bi-question-circle' ?>"></i>
                </div>
                <div>
                    <h2 class="h5 mb-0"><?= $p['confirmado'] ? '¡Participación confirmada!' : '¿Asistirás al intercambio?' ?></h2>
                    <p class="small text-muted mb-0"><?= e(fecha_hora($i['fecha_evento'])) ?> · <?= e($i['lugar'] ?: 'Lugar por definir') ?></p>
                </div>
            </div>
            <?php if ($puedeConfirmar): ?>
                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/confirmar') ?>">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="volver" value="perfil">
                    <input type="hidden" name="confirmado" value="<?= $p['confirmado'] ? '0' : '1' ?>">
                    <?php if ($p['confirmado']): ?>
                        <button class="btn btn-outline-secondary rounded-pill">Cancelar confirmación</button>
                    <?php else: ?>
                        <button class="btn btn-navidad"><i class="bi bi-check2"></i> Confirmar participación</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php $participante = $p; require RUTA_VISTAS . '/participante/_enlace-personal.php'; ?>

    <div class="row g-4">
        <!-- ===== Perfil ===== -->
        <div class="col-lg-5">
            <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/perfil') ?>"
                  enctype="multipart/form-data" class="needs-validation card tarjeta-intercambio tarjeta-form" novalidate>
                <div class="card-body p-4">
                    <?= Csrf::campo() ?>
                    <h2 class="h5 seccion-form"><span><i class="bi bi-person"></i></span> Sobre mí</h2>

                    <!-- Foto -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <?php if ($p['foto']): ?>
                            <img src="<?= url('uploads/' . e($p['foto'])) ?>" alt="Tu foto" class="avatar avatar-lg" id="vista-foto">
                        <?php else: ?>
                            <span class="avatar avatar-lg" id="vista-foto-iniciales"><?= e(iniciales($p['nombre'])) ?></span>
                            <img src="" alt="" class="avatar avatar-lg d-none" id="vista-foto">
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <label for="foto" class="form-label small mb-1">Fotografía (opcional)</label>
                            <input type="file" class="form-control form-control-sm<?= clase_error('foto') ?>" id="foto" name="foto"
                                   accept="image/jpeg,image/png,image/gif,image/webp" data-preview="vista-foto" data-max-mb="2">
                            <div class="invalid-feedback"><?= e(error_campo('foto')) ?: 'La imagen debe pesar máximo 2 MB.' ?></div>
                            <?php if ($p['foto']): ?>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="quitar_foto" value="1" id="quitar_foto">
                                    <label class="form-check-label small" for="quitar_foto">Quitar mi foto</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Tu nombre *</label>
                        <input type="text" class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre"
                               value="<?= old('nombre') !== '' ? old('nombre') : e($p['nombre']) ?>" required minlength="2" maxlength="120">
                        <div class="invalid-feedback"><?= e(error_campo('nombre')) ?: 'Escribe tu nombre.' ?></div>
                    </div>

                    <?php
                    // Campos de texto del perfil: [nombre, etiqueta, ejemplo]
                    $campos = [
                        ['gustos', 'Gustos personales', 'Café, novelas de misterio, plantas, colores pastel…'],
                        ['intereses', 'Intereses y hobbies', 'Cocinar, correr, videojuegos, fotografía…'],
                        ['no_le_gusta', 'Cosas que NO me gustaría recibir', 'Perfumes, ropa, dulces con nuez…'],
                        ['preferencias', 'Preferencias (tallas, colores, etc.)', 'Talla M, calzado 24, prefiero tonos verdes…'],
                        ['comentarios', 'Comentarios adicionales', 'Soy alérgica al látex. Me encantan las sorpresas hechas a mano.'],
                    ];
                    foreach ($campos as list($campo, $etiqueta, $ejemplo)):
                        $valor = old($campo) !== '' ? old($campo) : e($p[$campo]);
                    ?>
                        <div class="mb-3">
                            <label for="<?= $campo ?>" class="form-label"><?= e($etiqueta) ?></label>
                            <textarea class="form-control<?= clase_error($campo) ?>" id="<?= $campo ?>" name="<?= $campo ?>"
                                      rows="2" maxlength="1000" placeholder="<?= e($ejemplo) ?>"><?= $valor ?></textarea>
                            <div class="invalid-feedback"><?= e(error_campo($campo)) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-pino w-100"><i class="bi bi-save"></i> Guardar perfil</button>
                </div>
            </form>
        </div>

        <!-- ===== Lista de deseos ===== -->
        <div class="col-lg-7">
            <div class="card tarjeta-intercambio tarjeta-form mb-4" id="lista-deseos">
                <div class="card-body p-4">
                    <h2 class="h5 seccion-form"><span><i class="bi bi-gift"></i></span> Mi lista de deseos (<?= count($deseos) ?>)</h2>

                    <?php if (!$deseos): ?>
                        <p class="text-muted mb-0">Agrega tu primera idea de regalo. Entre más pistas des, ¡mejor regalo recibirás!</p>
                    <?php else: ?>
                        <div class="d-grid gap-3">
                            <?php foreach ($deseos as $d): list($pTexto, $pColor) = $prioridades[(int)$d['prioridad']]; ?>
                                <div class="deseo">
                                    <?php if ($d['imagen']): ?>
                                        <img src="<?= url('uploads/' . e($d['imagen'])) ?>" alt="" class="deseo-img">
                                    <?php else: ?>
                                        <div class="deseo-img deseo-img-vacia"><i class="bi bi-gift"></i></div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <h3 class="h6 mb-0"><?= e($d['titulo']) ?></h3>
                                            <span class="badge text-bg-<?= $pColor ?>">Prioridad <?= $pTexto ?></span>
                                        </div>
                                        <?php if ($d['descripcion']): ?>
                                            <p class="small text-muted mb-1"><?= nl2br(e($d['descripcion'])) ?></p>
                                        <?php endif; ?>
                                        <?php if ($d['enlace']): ?>
                                            <a href="<?= e($d['enlace']) ?>" target="_blank" rel="noopener noreferrer nofollow"
                                               class="small text-pino text-break"><i class="bi bi-box-arrow-up-right"></i> Ver producto</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-column gap-1">
                                        <a href="<?= url('deseos/' . (int)$d['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"
                                           aria-label="Editar"><i class="bi bi-pencil"></i></a>
                                        <form method="post" action="<?= url('deseos/' . (int)$d['id'] . '/eliminar') ?>"
                                              data-confirmar="¿Eliminar «<?= e($d['titulo']) ?>» de tu lista?">
                                            <?= Csrf::campo() ?>
                                            <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Agregar deseo -->
            <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/deseos') ?>" enctype="multipart/form-data"
                  class="needs-validation card tarjeta-intercambio tarjeta-form" id="nuevo-deseo" novalidate>
                <div class="card-body p-4">
                    <?= Csrf::campo() ?>
                    <h2 class="h5 seccion-form"><span><i class="bi bi-plus"></i></span> Agregar idea de regalo</h2>
                    <?php $d = null; /* formulario vacío (el foreach de arriba también usa $d) */ ?>
                    <?php require RUTA_VISTAS . '/participante/_campos-deseo.php'; ?>
                    <button type="submit" class="btn btn-navidad w-100"><i class="bi bi-plus-circle"></i> Agregar a mi lista</button>
                </div>
            </form>
        </div>
    </div>
</section>

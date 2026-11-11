<?php
/**
 * Archivo: app/views/participante/_campos-deseo.php
 * Campos del formulario de deseo. Se reutiliza para AGREGAR y para EDITAR.
 * Si existe $d (deseo que se edita), los campos se llenan con sus datos.
 */
$d = $d ?? null;
$valorDe = function ($campo) use ($d) {
    $o = old($campo);
    return $o !== '' ? $o : e($d[$campo] ?? '');
};
$prioridadActual = (int)($valorDe('prioridad') ?: 2);
?>
<div class="mb-3">
    <label for="titulo" class="form-label">Idea de regalo *</label>
    <input type="text" class="form-control<?= clase_error('titulo') ?>" id="titulo" name="titulo"
           value="<?= $valorDe('titulo') ?>" placeholder="Taza térmica de acero" required maxlength="150">
    <div class="invalid-feedback"><?= e(error_campo('titulo')) ?: 'Escribe la idea de regalo.' ?></div>
</div>

<div class="mb-3">
    <label for="descripcion_deseo" class="form-label">Detalles</label>
    <textarea class="form-control<?= clase_error('descripcion') ?>" id="descripcion_deseo" name="descripcion" rows="2"
              maxlength="1000" placeholder="Color verde, de unos 500 ml"><?= $valorDe('descripcion') ?></textarea>
    <div class="invalid-feedback"><?= e(error_campo('descripcion')) ?></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label for="enlace" class="form-label">Enlace del producto</label>
        <input type="url" class="form-control<?= clase_error('enlace') ?>" id="enlace" name="enlace"
               value="<?= $valorDe('enlace') ?>" placeholder="https://www.tienda.com/producto" maxlength="500"
               pattern="https?://.+">
        <div class="invalid-feedback"><?= e(error_campo('enlace')) ?: 'Debe empezar con https://' ?></div>
    </div>
    <div class="col-md-4">
        <label for="prioridad" class="form-label">Prioridad</label>
        <select class="form-select" id="prioridad" name="prioridad">
            <option value="1" <?= $prioridadActual === 1 ? 'selected' : '' ?>>Alta</option>
            <option value="2" <?= $prioridadActual === 2 ? 'selected' : '' ?>>Media</option>
            <option value="3" <?= $prioridadActual === 3 ? 'selected' : '' ?>>Baja</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <label for="imagen" class="form-label">Foto de referencia (opcional, máx. 2 MB)</label>
    <input type="file" class="form-control<?= clase_error('imagen') ?>" id="imagen" name="imagen"
           accept="image/jpeg,image/png,image/gif,image/webp" data-preview="vista-deseo" data-max-mb="2">
    <div class="invalid-feedback"><?= e(error_campo('imagen')) ?: 'La imagen debe pesar máximo 2 MB.' ?></div>
    <img src="<?= !empty($d['imagen']) ? url('uploads/' . e($d['imagen'])) : '' ?>" alt=""
         class="deseo-img mt-2 <?= empty($d['imagen']) ? 'd-none' : '' ?>" id="vista-deseo">
    <?php if (!empty($d['imagen'])): ?>
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="quitar_imagen" value="1" id="quitar_imagen">
            <label class="form-check-label small" for="quitar_imagen">Quitar la foto</label>
        </div>
    <?php endif; ?>
</div>

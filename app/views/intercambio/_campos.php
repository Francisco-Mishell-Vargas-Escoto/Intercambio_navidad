<?php
/**
 * Archivo: app/views/intercambio/_campos.php
 * Campos del formulario de intercambio. Se reutiliza en "Crear intercambio"
 * y en la pestaña "Configuración" del panel de administración.
 * Si existe $i (intercambio que se edita), los campos se llenan con sus datos.
 */
$i = $i ?? null;
$hayOld = isset($_SESSION['old']);   // se lee ANTES de llamar old(), que lo borra de la sesión
$ahora = date('Y-m-d\TH:i');
$valorDe = function ($campo) use ($i) {
    $o = old($campo);
    if ($o !== '') return $o;
    if (!$i) return '';
    if (in_array($campo, ['fecha_evento', 'fecha_limite'], true)) {
        return date('Y-m-d\TH:i', strtotime($i[$campo]));   // formato de datetime-local
    }
    return e((string)$i[$campo]);
};
?>
<!-- ===== 1. Datos generales ===== -->
<div class="card tarjeta-intercambio tarjeta-form mb-4">
    <div class="card-body p-4">
        <h2 class="h5 seccion-form"><span>1</span> Datos generales</h2>
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del intercambio *</label>
            <input type="text" class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre"
                   value="<?= $valorDe('nombre') ?>" placeholder="Intercambio Amigos 2026" required minlength="3" maxlength="120">
            <div class="invalid-feedback"><?= e(error_campo('nombre')) ?: 'Escribe un nombre (mínimo 3 letras).' ?></div>
        </div>
        <div class="mb-0">
            <label for="descripcion" class="form-label">Descripción o mensaje de bienvenida</label>
            <textarea class="form-control<?= clase_error('descripcion') ?>" id="descripcion" name="descripcion" rows="3" maxlength="1000"
                      placeholder="¡Este año celebramos juntos! Traigan su mejor sonrisa y un regalo envuelto con cariño."><?= $valorDe('descripcion') ?></textarea>
            <div class="invalid-feedback"><?= e(error_campo('descripcion')) ?></div>
        </div>
    </div>
</div>

<!-- ===== 2. Fechas y lugar ===== -->
<div class="card tarjeta-intercambio tarjeta-form mb-4">
    <div class="card-body p-4">
        <h2 class="h5 seccion-form"><span>2</span> Fechas y lugar</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="fecha_evento" class="form-label">Fecha y hora del intercambio *</label>
                <input type="datetime-local" class="form-control<?= clase_error('fecha_evento') ?>" id="fecha_evento"
                       name="fecha_evento" value="<?= $valorDe('fecha_evento') ?>" min="<?= $ahora ?>" required>
                <div class="invalid-feedback"><?= e(error_campo('fecha_evento')) ?: 'Elige una fecha futura.' ?></div>
            </div>
            <div class="col-md-6">
                <label for="fecha_limite" class="form-label">Fecha límite para registrarse *</label>
                <!-- data-max-de: app.js no deja elegir una fecha posterior al evento -->
                <input type="datetime-local" class="form-control<?= clase_error('fecha_limite') ?>" id="fecha_limite"
                       name="fecha_limite" value="<?= $valorDe('fecha_limite') ?>" min="<?= $ahora ?>" data-max-de="fecha_evento" required>
                <div class="invalid-feedback"><?= e(error_campo('fecha_limite')) ?: 'Debe ser futura y antes del intercambio.' ?></div>
                <div class="form-text">Después de esta fecha nadie más podrá unirse con el código.</div>
            </div>
            <div class="col-12">
                <label for="lugar" class="form-label">Lugar del evento</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                    <input type="text" class="form-control<?= clase_error('lugar') ?>" id="lugar" name="lugar"
                           value="<?= $valorDe('lugar') ?>" maxlength="200" placeholder="Casa de Ana, Av. Reforma 123">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== 3. Presupuesto ===== -->
<div class="card tarjeta-intercambio tarjeta-form mb-4">
    <div class="card-body p-4">
        <h2 class="h5 seccion-form"><span>3</span> Presupuesto</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="presupuesto_min" class="form-label">Mínimo *</label>
                <div class="input-group has-validation">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control<?= clase_error('presupuesto_min') ?>" id="presupuesto_min"
                           name="presupuesto_min" value="<?= $valorDe('presupuesto_min') ?>" min="0" step="0.01" placeholder="300" required>
                    <div class="invalid-feedback"><?= e(error_campo('presupuesto_min')) ?: 'Escribe una cantidad (0 o más).' ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <label for="presupuesto_max" class="form-label">Máximo *</label>
                <div class="input-group has-validation">
                    <span class="input-group-text">$</span>
                    <!-- data-min-de: app.js no deja un máximo menor que el mínimo -->
                    <input type="number" class="form-control<?= clase_error('presupuesto_max') ?>" id="presupuesto_max"
                           name="presupuesto_max" value="<?= $valorDe('presupuesto_max') ?>" min="0.01" step="0.01"
                           placeholder="500" data-min-de="presupuesto_min" required>
                    <div class="invalid-feedback"><?= e(error_campo('presupuesto_max')) ?: 'Debe ser mayor o igual al mínimo.' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== 4. Reglas ===== -->
<div class="card tarjeta-intercambio tarjeta-form mb-4">
    <div class="card-body p-4">
        <h2 class="h5 seccion-form"><span>4</span> Reglas y condiciones</h2>
        <textarea class="form-control<?= clase_error('reglas') ?>" id="reglas" name="reglas" rows="4" maxlength="2000"
                  placeholder="• El regalo debe ir envuelto y con el nombre de quien lo recibe.&#10;• Respetar el presupuesto.&#10;• ¡No revelar a tu amigo secreto antes del día!"><?= $valorDe('reglas') ?></textarea>
        <div class="invalid-feedback"><?= e(error_campo('reglas')) ?></div>
        <?php if (!empty($mostrarParticipa)): ?>
            <div class="form-check form-switch mt-4">
                <input class="form-check-input" type="checkbox" role="switch" id="participa" name="participa" value="1"
                    <?= (!$hayOld || old('participa') !== '') ? 'checked' : '' ?>>
                <label class="form-check-label" for="participa">Yo también participo en el intercambio</label>
            </div>
            <div class="form-text">Si lo desactivas, solo organizas y no entras al sorteo.</div>
        <?php endif; ?>
    </div>
</div>

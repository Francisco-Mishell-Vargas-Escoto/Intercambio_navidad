<?php
/**
 * Archivo: app/views/participante/_enlace-personal.php
 * Tarjeta con el enlace personal del participante (su "llave" para volver sin cuenta).
 * Variables: $i, $participante (o $p), $enlacePersonal, $destacar (bool, opcional)
 */
$yoP = $participante ?? $p;
$textoWa = 'Mi enlace personal para el intercambio "' . $i['nombre'] . '": ' . $enlacePersonal;
?>
<div class="card tarjeta-intercambio tarjeta-enlace mb-4 <?= !empty($destacar) ? 'destacada' : '' ?>">
    <div class="card-body p-4">
        <h2 class="h6 mb-1"><i class="bi bi-key-fill text-dorado"></i> Tu enlace personal</h2>
        <p class="small text-muted mb-3">
            <?php if (!empty($destacar)): ?><strong class="text-navidad">¡Guárdalo!</strong><?php endif; ?>
            Es tu llave para volver a este intercambio desde cualquier dispositivo sin crear cuenta.
            No lo compartas: quien lo tenga podrá ver a tu amigo secreto.
        </p>
        <div class="input-group mb-2">
            <input type="text" class="form-control form-control-sm" value="<?= e($enlacePersonal) ?>" readonly
                   aria-label="Tu enlace personal" onclick="this.select()">
            <button type="button" class="btn btn-sm btn-pino" data-copiar="<?= e($enlacePersonal) ?>">
                <i class="bi bi-copy"></i> Copiar</button>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-success rounded-pill" target="_blank" rel="noopener"
               href="https://wa.me/?text=<?= rawurlencode($textoWa) ?>">
                <i class="bi bi-whatsapp"></i> Enviármelo por WhatsApp</a>
            <?php if (empty($yoP['usuario_id'])): ?>
                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/salir') ?>"
                      data-confirmar="¿Salir en este dispositivo? Para volver necesitarás tu enlace personal.">
                    <?= Csrf::campo() ?>
                    <button class="btn btn-sm btn-link text-muted">No soy <?= e($yoP['nombre']) ?> · salir en este dispositivo</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

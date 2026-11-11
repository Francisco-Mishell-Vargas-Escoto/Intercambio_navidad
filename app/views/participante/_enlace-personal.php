<?php
/**
 * Archivo: app/views/participante/_enlace-personal.php
 * Tarjeta con el código y el enlace personal del participante (sus "llaves" para volver sin cuenta).
 * Variables: $i, $participante (o $p), $enlacePersonal, $destacar (bool, opcional)
 */
$yoP = $participante ?? $p;
$codigoP = CodigoService::formatearPersonal($yoP['codigo']);
$textoWa = 'Mis datos para el intercambio "' . $i['nombre'] . '". Código personal: ' . $codigoP
         . ' (entra en ' . url('entrar') . ') · Enlace directo: ' . $enlacePersonal;
?>
<div class="card tarjeta-intercambio tarjeta-enlace mb-4 <?= !empty($destacar) ? 'destacada' : '' ?>">
    <div class="card-body p-4">
        <h2 class="h6 mb-1"><i class="bi bi-key-fill text-dorado"></i> Tu código y tu enlace personal</h2>
        <p class="small text-muted mb-3">
            <?php if (!empty($destacar)): ?><strong class="text-navidad">¡Guárdalos!</strong><?php endif; ?>
            Son tus llaves para volver a este intercambio desde cualquier dispositivo sin crear cuenta:
            escribe tu código en <a href="<?= url('entrar') ?>" class="text-pino">Entrar</a> o abre tu enlace.
            No los compartas: quien los tenga podrá ver a tu amigo secreto.
        </p>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="codigo-personal" aria-label="Tu código personal"><?= e($codigoP) ?></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-copiar="<?= e($codigoP) ?>" aria-label="Copiar código">
                <i class="bi bi-copy"></i></button>
        </div>
        <div class="input-group mb-2">
            <input type="text" class="form-control form-control-sm" value="<?= e($enlacePersonal) ?>" readonly
                   aria-label="Tu enlace personal" onclick="this.select()">
            <button type="button" class="btn btn-sm btn-pino" data-copiar="<?= e($enlacePersonal) ?>">
                <i class="bi bi-copy"></i> Copiar</button>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-success rounded-pill" target="_blank" rel="noopener"
               href="https://wa.me/?text=<?= rawurlencode($textoWa) ?>">
                <i class="bi bi-whatsapp"></i> Enviármelos por WhatsApp</a>
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

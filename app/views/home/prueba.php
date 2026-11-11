<?php
/**
 * Archivo: app/views/home/prueba.php
 * Muestra el resultado de la prueba de conexión a MySQL.
 */
$esperadas = ['asignaciones', 'deseos', 'intercambios', 'invitaciones',
              'notificaciones', 'participantes', 'restricciones', 'usuarios'];
$faltan = array_diff($esperadas, $tablas);
?>
<section class="container my-5">
    <div class="card tarjeta-intercambio mx-auto" style="max-width: 560px">
        <div class="card-body p-4">
            <?php if (empty($faltan)): ?>
                <h1 class="h4 text-pino"><i class="bi bi-check-circle-fill"></i> Conexión correcta</h1>
                <p class="mb-3">PHP se conectó a MySQL <strong><?= e($version) ?></strong> y encontró las
                    <?= count($tablas) ?> tablas del sistema.</p>
            <?php else: ?>
                <h1 class="h4 text-navidad"><i class="bi bi-exclamation-triangle-fill"></i> Faltan tablas</h1>
                <p>Vuelve a importar <code>database/intercambio.sql</code>. Faltan:
                    <strong><?= e(implode(', ', $faltan)) ?></strong></p>
            <?php endif; ?>

            <ul class="list-group">
                <?php foreach ($esperadas as $t): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <code><?= e($t) ?></code>
                        <?= in_array($t, $tablas, true)
                            ? '<span class="text-pino"><i class="bi bi-check-lg"></i></span>'
                            : '<span class="text-navidad"><i class="bi bi-x-lg"></i></span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= url('/') ?>" class="btn btn-navidad mt-3">Volver al inicio</a>
        </div>
    </div>
</section>

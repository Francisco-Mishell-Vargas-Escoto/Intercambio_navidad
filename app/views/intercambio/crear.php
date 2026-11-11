<?php
/**
 * Archivo: app/views/intercambio/crear.php
 * Formulario para crear un intercambio (los campos están en _campos.php).
 */
$mostrarParticipa = true;   // el interruptor "Yo también participo" solo aparece al crear
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="text-center mb-4">
                <div class="icono-form"><i class="bi bi-magic"></i></div>
                <h1 class="h3 mt-2 mb-1">Crear intercambio</h1>
                <p class="text-muted mb-0">Configura los detalles. Al terminar recibirás un código privado para invitar a tus amigos.</p>
            </div>

            <form method="post" action="<?= url('intercambios/crear') ?>" class="needs-validation" novalidate>
                <?= Csrf::campo() ?>
                <?php require RUTA_VISTAS . '/intercambio/_campos.php'; ?>

                <div class="d-flex flex-wrap justify-content-end gap-2">
                    <a href="<?= url('mis-intercambios') ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
                    <button type="submit" class="btn btn-navidad btn-lg"><i class="bi bi-gift"></i> Crear intercambio</button>
                </div>
            </form>
        </div>
    </div>
</section>

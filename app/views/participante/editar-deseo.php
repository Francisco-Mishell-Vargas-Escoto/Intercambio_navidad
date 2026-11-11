<?php
/**
 * Archivo: app/views/participante/editar-deseo.php
 * Editar un deseo existente. Variable: $d
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <a href="<?= url('intercambios/' . (int)$d['intercambio_id'] . '/perfil#lista-deseos') ?>"
               class="small text-decoration-none text-pino"><i class="bi bi-arrow-left"></i> Volver a mi lista</a>

            <form method="post" action="<?= url('deseos/' . (int)$d['id'] . '/editar') ?>" enctype="multipart/form-data"
                  class="needs-validation card tarjeta-intercambio tarjeta-form mt-2" novalidate>
                <div class="card-body p-4">
                    <?= Csrf::campo() ?>
                    <h1 class="h4 seccion-form"><span><i class="bi bi-pencil"></i></span> Editar deseo</h1>
                    <?php require RUTA_VISTAS . '/participante/_campos-deseo.php'; ?>
                    <button type="submit" class="btn btn-pino w-100"><i class="bi bi-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</section>

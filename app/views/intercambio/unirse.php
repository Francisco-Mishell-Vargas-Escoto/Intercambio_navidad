<?php
/**
 * Archivo: app/views/intercambio/unirse.php
 * Formulario para escribir el código de invitación.
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5 text-center">
                    <div class="icono-form"><i class="bi bi-ticket-perforated"></i></div>
                    <h1 class="h3 mt-2 mb-1">Unirme a un intercambio</h1>
                    <p class="text-muted small mb-4">Escribe el código que te compartió el organizador.</p>

                    <form method="post" action="<?= url('unirse') ?>" class="needs-validation" novalidate>
                        <?= Csrf::campo() ?>
                        <input type="text" class="form-control form-control-lg input-codigo<?= clase_error('codigo') ?>"
                               name="codigo" id="codigo" value="<?= old('codigo') ?>" placeholder="NAV-7K2-Q9X"
                               required minlength="9" maxlength="15" autocomplete="off" autofocus
                               aria-label="Código de invitación">
                        <div class="invalid-feedback"><?= e(error_campo('codigo')) ?: 'Escribe el código completo.' ?></div>

                        <button type="submit" class="btn btn-pino w-100 py-2 mt-3">
                            <i class="bi bi-search"></i> Buscar intercambio
                        </button>
                    </form>
                    <p class="small text-muted mt-4 mb-0">¿Te enviaron un enlace? Ábrelo directamente y te traerá aquí.</p>
                </div>
            </div>
        </div>
    </div>
</section>

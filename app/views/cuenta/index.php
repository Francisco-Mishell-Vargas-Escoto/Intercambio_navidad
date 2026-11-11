<?php
/**
 * Archivo: app/views/cuenta/index.php
 * Datos de la cuenta del organizador. Variable: $yo
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icono-form"><i class="bi bi-person-gear"></i></div>
                        <h1 class="h3 mt-2 mb-1">Mi cuenta</h1>
                        <p class="text-muted small mb-0"><?= e($yo['email']) ?></p>
                    </div>

                    <form method="post" action="<?= url('mi-cuenta') ?>" class="needs-validation" novalidate>
                        <?= Csrf::campo() ?>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Tu nombre</label>
                            <input type="text" class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre"
                                   value="<?= old('nombre') !== '' ? old('nombre') : e($yo['nombre']) ?>"
                                   required minlength="3" maxlength="120" autocomplete="name">
                            <div class="invalid-feedback"><?= e(error_campo('nombre')) ?: 'Escribe tu nombre (mínimo 3 letras).' ?></div>
                            <div class="form-text">Es el nombre que ven los participantes en tus intercambios y en los que participas.</div>
                        </div>
                        <button type="submit" class="btn btn-navidad w-100"><i class="bi bi-save"></i> Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

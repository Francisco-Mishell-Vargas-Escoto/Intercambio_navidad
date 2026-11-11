<?php
/**
 * Archivo: app/views/intercambio/entrar.php
 * Entrar a un intercambio con el código personal (sin cuenta).
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5 text-center">
                    <div class="icono-form"><i class="bi bi-key"></i></div>
                    <h1 class="h3 mt-2 mb-1">Entrar con mi código</h1>
                    <p class="text-muted small mb-4">Escribe tu <strong>código personal</strong> de 8 caracteres.
                        Lo recibiste al unirte (también te lo puede reenviar el organizador).</p>

                    <form method="post" action="<?= url('entrar') ?>" class="needs-validation" novalidate>
                        <?= Csrf::campo() ?>
                        <input type="text" class="form-control form-control-lg input-codigo<?= clase_error('codigo') ?>"
                               name="codigo" id="codigo" value="<?= old('codigo') ?>" placeholder="K7M2-QX9P"
                               required minlength="8" maxlength="12" autocomplete="off" autocapitalize="characters" autofocus
                               aria-label="Código personal">
                        <div class="invalid-feedback"><?= e(error_campo('codigo')) ?: 'Escribe tu código completo (8 caracteres).' ?></div>

                        <button type="submit" class="btn btn-navidad w-100 py-2 mt-3">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="small text-muted mb-1">¿Todavía no te unes? <a href="<?= url('unirse') ?>" class="text-pino fw-medium">Unirme con el código del intercambio</a></p>
                    <p class="small text-muted mb-0">¿Eres organizador? <a href="<?= url('login') ?>" class="text-navidad fw-medium">Inicia sesión</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

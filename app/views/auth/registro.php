<?php
/**
 * Archivo: app/views/auth/registro.php
 * Formulario para crear una cuenta nueva.
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icono-form"><i class="bi bi-person-plus"></i></div>
                        <h1 class="h3 mt-2 mb-1">Crear cuenta</h1>
                        <p class="text-muted small mb-0">Con tu cuenta podrás organizar intercambios o unirte a los de tus amigos.</p>
                    </div>

                    <form method="post" action="<?= url('registro') ?>" class="needs-validation" novalidate>
                        <?= Csrf::campo() ?>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo</label>
                            <input type="text" class="form-control<?= clase_error('nombre') ?>" id="nombre" name="nombre"
                                   value="<?= old('nombre') ?>" placeholder="Ana López García"
                                   required minlength="3" maxlength="120" autocomplete="name" autofocus>
                            <div class="invalid-feedback"><?= e(error_campo('nombre')) ?: 'Escribe tu nombre (mínimo 3 letras).' ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control<?= clase_error('email') ?>" id="email" name="email"
                                   value="<?= old('email') ?>" placeholder="nombre@correo.com"
                                   required maxlength="150" autocomplete="email">
                            <div class="invalid-feedback"><?= e(error_campo('email')) ?: 'Escribe un correo válido.' ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group has-validation">
                                <!-- pattern: al menos una letra y un número, mínimo 8 caracteres -->
                                <input type="password" class="form-control<?= clase_error('password') ?>" id="password"
                                       name="password" required minlength="8"
                                       pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password">
                                <button class="btn btn-outline-secondary btn-ver-password" type="button"
                                        data-objetivo="password" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="invalid-feedback"><?= e(error_campo('password')) ?: 'Mínimo 8 caracteres, con letras y números.' ?></div>
                            </div>
                            <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirmar_password" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control<?= clase_error('confirmar_password') ?>"
                                   id="confirmar_password" name="confirmar_password" required
                                   data-igual-a="password" autocomplete="new-password">
                            <div class="invalid-feedback"><?= e(error_campo('confirmar_password')) ?: 'Las contraseñas no coinciden.' ?></div>
                        </div>

                        <button type="submit" class="btn btn-navidad w-100 py-2">
                            <i class="bi bi-gift"></i> Crear mi cuenta
                        </button>
                    </form>

                    <p class="text-center small mt-4 mb-0">¿Ya tienes cuenta?
                        <a href="<?= url('login') ?>" class="text-navidad fw-semibold">Inicia sesión</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

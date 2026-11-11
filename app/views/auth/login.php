<?php
/**
 * Archivo: app/views/auth/login.php
 * Formulario para iniciar sesión.
 */
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card tarjeta-intercambio tarjeta-form">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="icono-form"><i class="bi bi-door-open"></i></div>
                        <h1 class="h3 mt-2 mb-1">Iniciar sesión</h1>
                        <p class="text-muted small mb-0">Entra para ver tus intercambios y tu amigo secreto.</p>
                    </div>

                    <!-- novalidate: usamos nuestra validación JS (app.js) + la del servidor -->
                    <form method="post" action="<?= url('login') ?>" class="needs-validation" novalidate>
                        <?= Csrf::campo() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control<?= clase_error('email') ?>" id="email" name="email"
                                   value="<?= old('email') ?>" placeholder="nombre@correo.com" required maxlength="150"
                                   autocomplete="email" autofocus>
                            <div class="invalid-feedback"><?= e(error_campo('email')) ?: 'Escribe un correo válido.' ?></div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group has-validation">
                                <input type="password" class="form-control<?= clase_error('password') ?>" id="password"
                                       name="password" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary btn-ver-password" type="button"
                                        data-objetivo="password" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="invalid-feedback"><?= e(error_campo('password')) ?: 'Escribe tu contraseña.' ?></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-navidad w-100 py-2">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </button>
                    </form>

                    <p class="text-center small mt-4 mb-0">¿Aún no tienes cuenta?
                        <a href="<?= url('registro') ?>" class="text-navidad fw-semibold">Crear cuenta</a></p>
                </div>
            </div>
        </div>
    </div>
</section>

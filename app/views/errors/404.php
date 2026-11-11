<?php
/**
 * Archivo: app/views/errors/404.php
 * Se muestra cuando la URL no existe.
 */
?>
<section class="container text-center my-5 py-5">
    <div class="hero-icono mb-3 text-navidad"><i class="bi bi-snow2"></i></div>
    <h1 class="titulo-festivo">¡Ups! Este regalo no existe</h1>
    <p class="lead text-muted">La página que buscas se perdió en la nieve.</p>
    <a href="<?= url('/') ?>" class="btn btn-navidad mt-2"><i class="bi bi-house"></i> Volver al inicio</a>
</section>

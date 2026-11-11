<?php
/**
 * Archivo: app/views/faq/index.php
 * Preguntas frecuentes (acordeón de Bootstrap).
 */
$preguntas = [
    ['¿Cómo funciona el intercambio?',
     'El organizador crea el intercambio y comparte un código o enlace. Cada persona se une, llena su perfil y su lista de deseos. Cuando todos están listos, el organizador hace el sorteo y publica los resultados. Entonces cada quien descubre a quién le regala.'],
    ['¿Necesito una cuenta?',
     'Solo si quieres ORGANIZAR un intercambio. Para participar basta con tu nombre: al unirte recibes un enlace personal que funciona como tu llave para volver cuando quieras.'],
    ['¿Cómo me uno a un intercambio?',
     'Abre el enlace que te enviaron o entra a "Unirme" y escribe el código (por ejemplo NAV-7K2-Q9X). Escribe tu nombre y listo: ya estás dentro.'],
    ['¿Qué es mi enlace personal?',
     'Es una dirección única (por ejemplo …/p/7f3a…) que te identifica sin contraseña. Tu navegador lo recuerda, pero guárdalo (por ejemplo, envíatelo por WhatsApp) para entrar desde otro dispositivo. No lo compartas: quien lo tenga puede ver a tu amigo secreto.'],
    ['¿Qué es mi código personal?',
     'Es un código de 8 caracteres (por ejemplo K7M2-QX9P) que recibes al unirte, junto con tu enlace. Es lo más fácil de anotar o dictar: escríbelo en "Entrar con mi código" y vuelves a tu intercambio desde cualquier dispositivo. No es el código del intercambio (NAV-…), que es el mismo para todos.'],
    ['Perdí mi enlace personal, ¿qué hago?',
     'Si tienes tu código personal, entra con él en "Entrar con mi código". Si no, pídele al organizador que te lo reenvíe: él ve el código y el enlace de cada participante en su panel. Si crees que alguien más los vio, el organizador puede generar unos nuevos y los anteriores dejan de funcionar.'],
    ['¿El organizador puede ver a quién le tocó cada persona?',
     'No. El sistema solo le muestra cuántas asignaciones existen y cuántas personas ya vieron la suya. Nadie puede ver la lista completa: cada participante consulta únicamente a quién le regala.'],
    ['¿Puede tocarme regalarme a mí mismo?',
     'Nunca. El algoritmo garantiza que nadie se regale a sí mismo, que cada persona dé exactamente un regalo y que reciba exactamente uno.'],
    ['¿Qué son las restricciones?',
     'Son combinaciones que el organizador prohíbe, por ejemplo que una pareja o dos hermanos se regalen entre sí. El sistema revisa que, aun con las restricciones, el sorteo siga siendo posible.'],
    ['¿Puedo saber quién me va a regalar a mí?',
     '¡No, esa es la sorpresa! Tu lista de deseos y tus gustos los verá solo la persona que te regala, sin que tú sepas quién es.'],
    ['¿Puedo cambiar mi lista de deseos después del sorteo?',
     'Sí. Puedes agregar, editar o quitar ideas en cualquier momento; tu amigo secreto verá siempre la versión más reciente.'],
    ['¿Qué pasa si alguien ya no puede participar?',
     'Antes del sorteo, el organizador puede eliminarlo desde el panel. Si el sorteo ya se hizo pero aún no se publican los resultados, el organizador puede reiniciarlo, quitar a esa persona y sortear de nuevo.'],
    ['¿Por qué no me deja unirme?',
     'Puede ser que ya pasó la fecha límite de registro, que el organizador cerró el registro o que ya se hizo el sorteo. Pide al organizador una invitación personal (funciona mientras no se haya hecho el sorteo).'],
    ['¿Mis datos están seguros?',
     'Las contraseñas de los organizadores se guardan cifradas (con hash). Tu enlace personal tiene 64 caracteres aleatorios, imposible de adivinar. Cada intercambio solo lo ven sus participantes y las fotos se revisan antes de guardarse.'],
];
?>
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-4">
                <div class="icono-form"><i class="bi bi-question-diamond"></i></div>
                <h1 class="titulo-festivo mt-2 mb-1">Preguntas frecuentes</h1>
                <p class="text-muted">Todo lo que necesitas saber para que tu intercambio salga perfecto.</p>
            </div>

            <div class="accordion acordeon-navidad" id="faq">
                <?php foreach ($preguntas as $n => list($pregunta, $respuesta)): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $n ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq-<?= $n ?>" aria-expanded="<?= $n ? 'false' : 'true' ?>" aria-controls="faq-<?= $n ?>">
                                <?= e($pregunta) ?>
                            </button>
                        </h2>
                        <div id="faq-<?= $n ?>" class="accordion-collapse collapse <?= $n ? '' : 'show' ?>" data-bs-parent="#faq">
                            <div class="accordion-body"><?= e($respuesta) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted mb-2">¿Listo para empezar?</p>
                <a href="<?= url('intercambios/crear') ?>" class="btn btn-navidad"><i class="bi bi-plus-circle"></i> Crear intercambio</a>
            </div>
        </div>
    </div>
</section>

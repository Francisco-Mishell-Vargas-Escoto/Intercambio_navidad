/**
 * Archivo: public/assets/js/app.js
 * Funciones generales del sitio.
 */

// ---------- Validación de formularios en el navegador ----------
// Todo <form class="needs-validation"> se revisa antes de enviarse.
// (El servidor vuelve a validar todo: esto solo da avisos rápidos.)
document.querySelectorAll('form.needs-validation').forEach(function (form) {

    // Campos que deben ser iguales a otro (confirmar contraseña)
    const confirmaciones = form.querySelectorAll('[data-igual-a]');
    function revisarIguales() {
        confirmaciones.forEach(function (campo) {
            const original = form.querySelector('#' + campo.dataset.igualA);
            campo.setCustomValidity(original && campo.value !== original.value ? 'No coincide' : '');
        });
    }
    form.addEventListener('input', revisarIguales);

    // Al escribir, quitamos el rojo que puso el servidor
    form.querySelectorAll('.is-invalid').forEach(function (campo) {
        campo.addEventListener('input', function () { campo.classList.remove('is-invalid'); }, { once: true });
    });

    form.addEventListener('submit', function (evento) {
        revisarIguales();
        if (!form.checkValidity()) {
            evento.preventDefault();          // no se envía
            evento.stopPropagation();
            const primero = form.querySelector(':invalid');
            if (primero) primero.focus();
        } else {
            // Evita doble clic: deshabilita el botón mientras se envía
            const boton = form.querySelector('[type="submit"]');
            if (boton) { boton.disabled = true; boton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando…'; }
        }
        form.classList.add('was-validated');
    });
});

// ---------- Botón para mostrar / ocultar contraseña ----------
document.querySelectorAll('.btn-ver-password').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = document.getElementById(boton.dataset.objetivo);
        const visible = campo.type === 'text';
        campo.type = visible ? 'password' : 'text';
        boton.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    });
});

// ---------- Límites que dependen de otro campo ----------
// data-max-de="fecha_evento": la fecha límite no puede pasar del día del evento
// data-min-de="presupuesto_min": el máximo no puede ser menor que el mínimo
document.querySelectorAll('[data-max-de], [data-min-de]').forEach(function (campo) {
    const idOtro = campo.dataset.maxDe || campo.dataset.minDe;
    const atributo = campo.dataset.maxDe ? 'max' : 'min';
    const otro = document.getElementById(idOtro);
    if (!otro) return;
    const minOriginal = campo.getAttribute('min');
    function sincronizar() {
        if (otro.value) {
            campo.setAttribute(atributo, otro.value);
        } else if (atributo === 'min' && minOriginal !== null) {
            campo.setAttribute('min', minOriginal);
        }
    }
    otro.addEventListener('input', sincronizar);
    sincronizar();
});

// ---------- Copiar al portapapeles ----------
// <button data-copiar="texto">  copia el texto y avisa en el mismo botón
document.querySelectorAll('[data-copiar]').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const texto = boton.dataset.copiar;
        const original = boton.innerHTML;
        function avisar() {
            boton.innerHTML = '<i class="bi bi-check2"></i> ¡Copiado!';
            setTimeout(function () { boton.innerHTML = original; }, 2000);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(avisar);
        } else {
            // Respaldo para navegadores viejos o páginas sin https
            const area = document.createElement('textarea');
            area.value = texto;
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            area.remove();
            avisar();
        }
    });
});

// ---------- Compartir con el menú nativo del celular ----------
document.querySelectorAll('[data-compartir]').forEach(function (boton) {
    if (!navigator.share) return;          // si el navegador no lo soporta, el botón queda oculto
    boton.classList.remove('d-none');
    boton.addEventListener('click', function () {
        navigator.share({ title: boton.dataset.titulo, text: boton.dataset.texto }).catch(function () {});
    });
});

// ---------- Vista previa y tamaño de imágenes ----------
// <input type="file" data-preview="idImagen" data-max-mb="2">
document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
        const archivo = input.files[0];
        const maxMb = parseFloat(input.dataset.maxMb || '2');
        input.setCustomValidity('');
        input.classList.remove('is-invalid');
        if (!archivo) return;

        if (archivo.size > maxMb * 1024 * 1024) {
            input.setCustomValidity('Muy pesada');
            input.classList.add('is-invalid');
            return;
        }
        const img = document.getElementById(input.dataset.preview);
        if (img) {
            img.src = URL.createObjectURL(archivo);
            img.classList.remove('d-none');
            const iniciales = document.getElementById(input.dataset.preview + '-iniciales');
            if (iniciales) iniciales.classList.add('d-none');
        }
    });
});

// ---------- Confirmar antes de enviar (ej. eliminar) ----------
// <form data-confirmar="¿Seguro?">  — usa un modal de Bootstrap, no alert()
document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (evento) {
        if (form.dataset.confirmado === '1') return;   // ya aceptó
        evento.preventDefault();
        confirmarAccion(form.dataset.confirmar, function () {
            form.dataset.confirmado = '1';
            // Botones data-una-vez: se desactivan para que no se pueda enviar dos veces
            form.querySelectorAll('[data-una-vez]').forEach(function (b) {
                b.disabled = true;
                b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando…';
            });
            form.submit();
        });
    });
});

/** Muestra un modal de confirmación y ejecuta alAceptar si el usuario dice que sí. */
function confirmarAccion(mensaje, alAceptar) {
    let modal = document.getElementById('modal-confirmar');
    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'modal-confirmar';
        modal.tabIndex = -1;
        modal.innerHTML =
            '<div class="modal-dialog modal-dialog-centered"><div class="modal-content tarjeta-intercambio">' +
            '<div class="modal-body p-4 text-center"><div class="icono-form mb-3"><i class="bi bi-question-lg"></i></div>' +
            '<p class="mb-0" data-mensaje></p></div>' +
            '<div class="modal-footer justify-content-center border-0 pt-0">' +
            '<button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>' +
            '<button type="button" class="btn btn-navidad" data-aceptar>Sí, continuar</button></div></div></div>';
        document.body.appendChild(modal);
    }
    modal.querySelector('[data-mensaje]').textContent = mensaje;
    const instancia = bootstrap.Modal.getOrCreateInstance(modal);
    modal.querySelector('[data-aceptar]').onclick = function () { instancia.hide(); alAceptar(); };
    instancia.show();
}

// ---------- Pestañas que se recuerdan en la URL (#participantes) ----------
// Así, después de guardar algo, el panel vuelve a abrir la misma pestaña.
(function () {
    const botones = document.querySelectorAll('[data-bs-toggle="pill"], [data-bs-toggle="tab"]');
    if (!botones.length) return;

    function abrirDesdeHash() {
        if (!location.hash) return;
        const boton = document.querySelector('[data-bs-target="' + location.hash + '"]');
        if (boton) bootstrap.Tab.getOrCreateInstance(boton).show();
    }
    botones.forEach(function (boton) {
        boton.addEventListener('shown.bs.tab', function () {
            history.replaceState(null, '', boton.dataset.bsTarget);   // cambia el # sin saltar
        });
    });
    // Si hay un error de validación dentro de una pestaña, la abrimos
    const conError = document.querySelector('.tab-pane .is-invalid');
    if (conError) {
        const panel = conError.closest('.tab-pane');
        const boton = document.querySelector('[data-bs-target="#' + panel.id + '"]');
        if (boton) bootstrap.Tab.getOrCreateInstance(boton).show();
    } else {
        abrirDesdeHash();
    }
    window.addEventListener('hashchange', abrirDesdeHash);
})();

// ---------- Cuenta regresiva ----------
// Busca todos los elementos .cuenta-regresiva y usa su atributo data-fecha
document.querySelectorAll('.cuenta-regresiva').forEach(function (caja) {
    const destino = new Date(caja.dataset.fecha).getTime();

    function actualizar() {
        let resta = Math.max(0, destino - Date.now());   // milisegundos que faltan

        const dias = Math.floor(resta / 86400000);  resta %= 86400000;
        const horas = Math.floor(resta / 3600000);  resta %= 3600000;
        const minutos = Math.floor(resta / 60000);  resta %= 60000;
        const segundos = Math.floor(resta / 1000);

        const valores = { dias, horas, minutos, segundos };
        for (const unidad in valores) {
            const span = caja.querySelector(`[data-unidad="${unidad}"]`);
            if (span) span.textContent = String(valores[unidad]).padStart(2, '0');
        }

        if (destino <= Date.now()) {
            caja.outerHTML = '<p class="text-center fs-5 text-navidad mt-4">¡Llegó el día del intercambio! 🎁</p>';
            clearInterval(intervalo);
        }
    }

    const intervalo = setInterval(actualizar, 1000);
    actualizar();
});

// ---------- Mensaje navideño aleatorio (página de inicio) ----------
(function () {
    const boton = document.getElementById('otro-mensaje');
    const texto = document.getElementById('mensaje-dia');
    if (!boton || !texto) return;
    const mensajes = JSON.parse(boton.dataset.mensajes);
    boton.addEventListener('click', function () {
        let nuevo;
        do {
            nuevo = mensajes[Math.floor(Math.random() * mensajes.length)];
        } while (nuevo === texto.textContent && mensajes.length > 1);   // que no repita el mismo
        texto.classList.remove('aparecer-texto');
        void texto.offsetWidth;                                        // reinicia la animación
        texto.textContent = nuevo;
        texto.classList.add('aparecer-texto');
    });
})();

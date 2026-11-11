/**
 * Archivo: public/assets/js/revelacion.js
 * Animación de revelación: el regalo tiembla, se abre la tapa, sale confeti
 * y aparece el nombre del amigo secreto.
 */
(function () {
    const escenario = document.getElementById('escenario');
    if (!escenario) return;
    const regalo = document.getElementById('regalo');
    const repetir = document.getElementById('repetir');
    const canvas = document.getElementById('confeti');
    const ctx = canvas.getContext('2d');
    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let abriendo = false;

    // ---------- 1. Abrir el regalo ----------
    function abrir() {
        if (abriendo) return;
        abriendo = true;

        if (sinMovimiento) {               // sin animaciones para quien lo pidió en su sistema
            mostrar();
            return;
        }
        regalo.classList.add('temblando');               // 1) tiembla
        setTimeout(function () {
            regalo.classList.remove('temblando');
            regalo.classList.add('abriendo');            // 2) la tapa sale volando
            lanzarConfeti();                             // 3) confeti
            setTimeout(mostrar, 650);                    // 4) aparece el nombre
        }, 1100);
    }

    function mostrar() {
        escenario.classList.add('abierto');
        abriendo = false;
        avisarAlServidor();
        const nombre = escenario.querySelector('.nombre-receptor');
        if (nombre) nombre.focus({ preventScroll: true });
    }

    // ---------- 2. Avisar al servidor que ya lo vio (para las estadísticas del organizador) ----------
    function avisarAlServidor() {
        if (escenario.dataset.yaVisto === '1') return;
        escenario.dataset.yaVisto = '1';
        fetch(escenario.dataset.urlVisto, {
            method: 'POST',
            headers: { 'X-CSRF-Token': escenario.dataset.csrf },
            credentials: 'same-origin'
        }).catch(function () { /* si falla no pasa nada: solo es una estadística */ });
    }

    // ---------- 3. Confeti con los colores navideños ----------
    function lanzarConfeti() {
        const rect = escenario.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        const colores = ['#b3122e', '#165b33', '#d4a537', '#ffffff', '#e8455f'];
        const origenX = canvas.width / 2;
        const origenY = canvas.height * 0.55;

        const piezas = [];
        for (let n = 0; n < 160; n++) {
            const angulo = Math.random() * Math.PI * 2;
            const fuerza = Math.random() * 9 + 4;
            piezas.push({
                x: origenX, y: origenY,
                vx: Math.cos(angulo) * fuerza,
                vy: Math.sin(angulo) * fuerza - 6,          // hacia arriba
                tam: Math.random() * 7 + 4,
                giro: Math.random() * Math.PI,
                vGiro: (Math.random() - 0.5) * 0.3,
                color: colores[Math.floor(Math.random() * colores.length)],
                vida: 1
            });
        }

        function cuadro() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let vivas = 0;
            piezas.forEach(function (p) {
                if (p.vida <= 0) return;
                vivas++;
                p.vy += 0.25;              // gravedad
                p.vx *= 0.99;              // resistencia del aire
                p.x += p.vx;
                p.y += p.vy;
                p.giro += p.vGiro;
                p.vida -= 0.006;
                ctx.save();
                ctx.globalAlpha = Math.max(0, p.vida);
                ctx.translate(p.x, p.y);
                ctx.rotate(p.giro);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.tam / 2, -p.tam / 4, p.tam, p.tam / 2);
                ctx.restore();
            });
            if (vivas > 0) requestAnimationFrame(cuadro);
            else ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        cuadro();
    }

    regalo.addEventListener('click', abrir);

    // ---------- 4. Repetir la animación ----------
    if (repetir) {
        repetir.addEventListener('click', function () {
            escenario.classList.remove('abierto');
            regalo.classList.remove('abriendo');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(abrir, 400);
        });
    }
})();

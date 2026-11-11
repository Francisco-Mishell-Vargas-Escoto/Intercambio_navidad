/**
 * Archivo: public/assets/js/nieve.js
 * Animación sutil de nieve cayendo sobre un <canvas>.
 */
(function () {
    const canvas = document.getElementById('nieve');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    let copos = [];

    // Ajusta el tamaño del lienzo a la ventana y crea los copos
    function iniciar() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        // Menos copos en celular para no gastar batería
        const cantidad = window.innerWidth < 768 ? 40 : 90;
        copos = Array.from({ length: cantidad }, crearCopo);
    }

    function crearCopo() {
        return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            radio: Math.random() * 2.5 + 1,          // tamaño
            velocidad: Math.random() * 0.8 + 0.3,    // caída
            viento: Math.random() * 0.6 - 0.3,       // movimiento lateral
            opacidad: Math.random() * 0.5 + 0.3,
        };
    }

    function dibujar() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (const c of copos) {
            ctx.beginPath();
            ctx.arc(c.x, c.y, c.radio, 0, Math.PI * 2);
            // Copos blancos con un borde azulado para que se vean sobre fondo claro
            ctx.fillStyle = `rgba(255, 255, 255, ${c.opacidad})`;
            ctx.strokeStyle = `rgba(160, 180, 200, ${c.opacidad * 0.6})`;
            ctx.fill();
            ctx.stroke();

            c.y += c.velocidad;
            c.x += c.viento;

            // Cuando sale de la pantalla vuelve a empezar arriba
            if (c.y > canvas.height) { c.y = -5; c.x = Math.random() * canvas.width; }
            if (c.x > canvas.width) c.x = 0;
            if (c.x < 0) c.x = canvas.width;
        }
        requestAnimationFrame(dibujar);
    }

    window.addEventListener('resize', iniciar);
    iniciar();
    dibujar();
})();

<?php
/**
 * Archivo: app/services/IntercambioValidacion.php
 * Validación de los datos de un intercambio. La usan dos pantallas:
 * "Crear intercambio" y "Configuración" del panel de administración.
 */

require_once RUTA_APP . '/core/Validator.php';

class IntercambioValidacion
{
    /** Campos del formulario que se recuerdan si hay errores. */
    const CAMPOS = ['nombre', 'descripcion', 'fecha_evento', 'fecha_limite', 'presupuesto_min',
                    'presupuesto_max', 'lugar', 'reglas', 'participa'];

    /**
     * Valida $_POST y devuelve [datos limpios, validador].
     * Los datos solo se deben usar si $validador->falla() es false.
     */
    public static function validar(array $post): array
    {
        $v = new Validator($post);
        $v->requerido('nombre', 'El nombre')->min('nombre', 3, 'El nombre')->max('nombre', 120, 'El nombre')
          ->max('descripcion', 1000, 'La descripción')
          ->requerido('fecha_evento', 'La fecha del intercambio')
          ->requerido('fecha_limite', 'La fecha límite')
          ->requerido('presupuesto_min', 'El presupuesto mínimo')
          ->requerido('presupuesto_max', 'El presupuesto máximo')
          ->max('lugar', 200, 'El lugar')
          ->max('reglas', 2000, 'Las reglas');

        // --- Fechas: el input datetime-local envía "2026-12-20T19:00" ---
        $evento = self::leerFecha($v->valor('fecha_evento'));
        $limite = self::leerFecha($v->valor('fecha_limite'));

        if ($v->valor('fecha_evento') !== '' && !$evento) {
            $v->error('fecha_evento', 'La fecha del intercambio no es válida.');
        } elseif ($evento && $evento->getTimestamp() <= time()) {
            $v->error('fecha_evento', 'La fecha del intercambio debe ser en el futuro.');
        }
        if ($v->valor('fecha_limite') !== '' && !$limite) {
            $v->error('fecha_limite', 'La fecha límite no es válida.');
        } elseif ($limite && $evento && $limite > $evento) {
            $v->error('fecha_limite', 'La fecha límite debe ser antes (o igual) que el día del intercambio.');
        } elseif ($limite && $limite->getTimestamp() <= time()) {
            $v->error('fecha_limite', 'La fecha límite debe ser en el futuro.');
        }

        // --- Presupuesto: números positivos y mínimo <= máximo ---
        $min = $v->valor('presupuesto_min');
        $max = $v->valor('presupuesto_max');
        if ($min !== '' && (!is_numeric($min) || $min < 0)) {
            $v->error('presupuesto_min', 'Escribe una cantidad válida (0 o más).');
        }
        if ($max !== '' && (!is_numeric($max) || $max <= 0)) {
            $v->error('presupuesto_max', 'Escribe una cantidad mayor a 0.');
        }
        if (is_numeric($min) && is_numeric($max) && (float)$min > (float)$max) {
            $v->error('presupuesto_max', 'El máximo debe ser mayor o igual al mínimo.');
        }

        $datos = [];
        if (!$v->falla()) {
            $datos = [
                'nombre'          => $v->valor('nombre'),
                'descripcion'     => $v->valor('descripcion') ?: null,
                'fecha_evento'    => $evento->format('Y-m-d H:i:s'),
                'fecha_limite'    => $limite->format('Y-m-d H:i:s'),
                'presupuesto_min' => round((float)$min, 2),
                'presupuesto_max' => round((float)$max, 2),
                'lugar'           => $v->valor('lugar') ?: null,
                'reglas'          => $v->valor('reglas') ?: null,
            ];
        }
        return [$datos, $v];
    }

    /** Convierte "2026-12-20T19:00" en un objeto DateTime (o null si es inválida). */
    private static function leerFecha(string $texto): ?DateTime
    {
        // Algunos navegadores envían segundos ("2026-12-20T19:00:00"): aceptamos ambos formatos
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $formato) {
            $f = DateTime::createFromFormat($formato, $texto);
            if ($f && $f->format($formato) === $texto) {
                return $f;
            }
        }
        return null;
    }
}

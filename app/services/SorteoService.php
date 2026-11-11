<?php
/**
 * Archivo: app/services/SorteoService.php
 * ALGORITMO DEL SORTEO (no usa la base de datos, así se puede probar solo).
 *
 * Reglas que garantiza:
 *  1. Nadie se regala a sí mismo.
 *  2. Cada persona da exactamente UN regalo.
 *  3. Cada persona recibe exactamente UN regalo.
 *  4. Se respetan las restricciones ("A no puede regalarle a B").
 *  5. Si existe al menos una asignación válida, la encuentra; si no existe, devuelve null.
 *
 * Cómo funciona (backtracking aleatorio):
 *  - Para cada persona calculamos la lista de a quién SÍ puede regalarle y la barajamos.
 *  - Empezamos por quien tiene MENOS opciones (así nos atoramos menos).
 *  - Le asignamos el primer receptor libre de su lista y pasamos a la siguiente persona.
 *  - Si alguien se queda sin opciones, regresamos un paso (backtracking) y probamos otra.
 */

class SorteoService
{
    /** Límite de pasos para no colgar el servidor con restricciones absurdas. */
    const MAX_PASOS = 500000;

    private $opciones = [];   // dador => [receptores permitidos]
    private $orden = [];      // orden en el que se asigna
    private $resultado = [];  // dador => receptor
    private $ocupados = [];   // receptor => true (ya tiene quien le regale)
    private $pasos = 0;

    /**
     * @param int[]   $ids         IDs de los participantes
     * @param array[] $prohibidos  pares [dador, receptor] que NO se permiten
     * @return array|null  [dador => receptor] o null si es imposible
     */
    public function asignar(array $ids, array $prohibidos = []): ?array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (count($ids) < 2) {
            return null;
        }

        // Conjunto de combinaciones prohibidas para buscar rápido: "5-9" => true
        $no = [];
        foreach ($prohibidos as $par) {
            $no[(int)$par[0] . '-' . (int)$par[1]] = true;
        }

        // 1) Opciones de cada dador (todos menos él mismo y los prohibidos)
        $this->opciones = [];
        $recibeAlguien = [];
        foreach ($ids as $dador) {
            $lista = [];
            foreach ($ids as $receptor) {
                if ($dador !== $receptor && !isset($no[$dador . '-' . $receptor])) {
                    $lista[] = $receptor;
                    $recibeAlguien[$receptor] = true;
                }
            }
            if (!$lista) {
                return null;                     // alguien no puede regalarle a nadie
            }
            $this->opciones[$dador] = self::mezclar($lista);
        }
        if (count($recibeAlguien) < count($ids)) {
            return null;                         // alguien no podría recibir de nadie
        }

        // 2) Orden: primero los que tienen menos opciones (empates al azar)
        $this->orden = self::mezclar($ids);
        $opciones = $this->opciones;
        usort($this->orden, function ($a, $b) use ($opciones) {
            return count($opciones[$a]) - count($opciones[$b]);
        });

        // 3) Búsqueda
        $this->resultado = [];
        $this->ocupados = [];
        $this->pasos = 0;
        return $this->buscar(0) ? $this->resultado : null;
    }

    /** Asigna al dador en la posición $k y sigue con los demás (recursivo). */
    private function buscar(int $k): bool
    {
        if ($k === count($this->orden)) {
            return true;                         // ¡todos tienen a quién regalar!
        }
        if (++$this->pasos > self::MAX_PASOS) {
            return false;
        }

        $dador = $this->orden[$k];
        foreach ($this->opciones[$dador] as $receptor) {
            if (isset($this->ocupados[$receptor])) {
                continue;                        // ese receptor ya tiene quien le regale
            }
            $this->resultado[$dador] = $receptor;
            $this->ocupados[$receptor] = true;

            if ($this->buscar($k + 1)) {
                return true;
            }
            // No funcionó: deshacemos y probamos con el siguiente (backtracking)
            unset($this->resultado[$dador], $this->ocupados[$receptor]);
        }
        return false;
    }

    /** Baraja un arreglo con random_int (aleatoriedad criptográfica, más justa que shuffle). */
    public static function mezclar(array $lista): array
    {
        $lista = array_values($lista);
        for ($i = count($lista) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $tmp = $lista[$i];
            $lista[$i] = $lista[$j];
            $lista[$j] = $tmp;
        }
        return $lista;
    }

    /**
     * Verifica que un resultado cumpla TODAS las reglas.
     * Se usa en las pruebas y como doble seguridad antes de guardar.
     */
    public static function esValido(array $ids, array $resultado, array $prohibidos = []): bool
    {
        $ids = array_map('intval', $ids);
        sort($ids);
        $dadores = array_keys($resultado);
        $receptores = array_values($resultado);
        sort($dadores);
        $copia = $receptores;
        sort($copia);

        if ($dadores !== $ids || $copia !== $ids) {
            return false;                        // alguien no da o no recibe (o recibe dos veces)
        }
        $no = [];
        foreach ($prohibidos as $par) {
            $no[(int)$par[0] . '-' . (int)$par[1]] = true;
        }
        foreach ($resultado as $d => $r) {
            if ($d === $r || isset($no[$d . '-' . $r])) {
                return false;                    // se regala a sí mismo o rompe una restricción
            }
        }
        return true;
    }
}

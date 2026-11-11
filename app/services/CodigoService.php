<?php
/**
 * Archivo: app/services/CodigoService.php
 * Genera códigos de acceso privados, únicos y fáciles de dictar.
 * Ejemplo: NAV7K2Q9X  (se muestra como NAV-7K2-Q9X)
 */

class CodigoService
{
    // Sin 0/O ni 1/I/L para que no se confundan al escribirlos
    const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Crea un código que no exista todavía en la tabla intercambios. */
    public static function generarUnico(PDO $db): string
    {
        $st = $db->prepare('SELECT COUNT(*) FROM intercambios WHERE codigo_acceso = ?');
        do {
            $codigo = 'NAV' . self::aleatorio(6);
            $st->execute([$codigo]);
        } while ((int)$st->fetchColumn() > 0);   // si ya existe (muy raro), generamos otro
        return $codigo;
    }

    /** Cadena aleatoria segura (random_int usa el generador criptográfico del sistema). */
    public static function aleatorio(int $largo): string
    {
        $max = strlen(self::ALFABETO) - 1;
        $txt = '';
        for ($i = 0; $i < $largo; $i++) {
            $txt .= self::ALFABETO[random_int(0, $max)];
        }
        return $txt;
    }

    /** Limpia lo que escribe el usuario: "nav-7k2 q9x" => "NAV7K2Q9X" */
    public static function normalizar(string $codigo): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $codigo));
    }

    /** Formato bonito para mostrar: NAV7K2Q9X => NAV-7K2-Q9X */
    public static function formatear(string $codigo): string
    {
        return implode('-', str_split($codigo, 3));
    }
}

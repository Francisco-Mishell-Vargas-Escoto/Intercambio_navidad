<?php
/**
 * Archivo: app/services/UploadService.php
 * Sube imágenes de forma segura (fotos de perfil y de la lista de deseos).
 *
 * Reglas de seguridad:
 *  - Solo JPG, PNG, GIF o WEBP, máximo 2 MB.
 *  - Revisamos el CONTENIDO real del archivo con getimagesize(), no solo su extensión.
 *  - El archivo se guarda con un nombre aleatorio (nadie puede adivinarlo ni sobrescribir otro).
 *  - En public/uploads hay un .htaccess que impide ejecutar PHP.
 */

class UploadService
{
    const MAX_BYTES = 2097152;   // 2 MB
    const TIPOS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Procesa $_FILES['campo'].
     * Devuelve ['archivo' => 'fotos/abc.jpg', 'error' => null]
     *       o ['archivo' => null, 'error' => 'mensaje']
     *       o ['archivo' => null, 'error' => null] si no se eligió archivo (es opcional).
     */
    public static function imagen(?array $file, string $carpeta): array
    {
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['archivo' => null, 'error' => null];
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return ['archivo' => null, 'error' => 'La imagen pesa más de 2 MB.'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return ['archivo' => null, 'error' => 'No se pudo subir la imagen. Inténtalo de nuevo.'];
        }
        if ($file['size'] > self::MAX_BYTES) {
            return ['archivo' => null, 'error' => 'La imagen pesa más de 2 MB.'];
        }

        // ¿Es de verdad una imagen? (un .php renombrado a .jpg falla aquí)
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !isset(self::TIPOS[$info[2]])) {
            return ['archivo' => null, 'error' => 'El archivo debe ser una imagen JPG, PNG, GIF o WEBP.'];
        }

        $destinoCarpeta = RUTA_UPLOADS . '/' . $carpeta;
        if (!is_dir($destinoCarpeta)) {
            mkdir($destinoCarpeta, 0755, true);
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$info[2]];
        if (!move_uploaded_file($file['tmp_name'], $destinoCarpeta . '/' . $nombre)) {
            return ['archivo' => null, 'error' => 'No se pudo guardar la imagen en el servidor.'];
        }
        return ['archivo' => $carpeta . '/' . $nombre, 'error' => null];
    }

    /** Borra una imagen anterior (por ejemplo, al cambiar la foto de perfil). */
    public static function borrar(?string $archivo): void
    {
        if (!$archivo) return;
        // basename evita rutas como "../../config/config.php"
        $ruta = RUTA_UPLOADS . '/' . basename(dirname($archivo)) . '/' . basename($archivo);
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}

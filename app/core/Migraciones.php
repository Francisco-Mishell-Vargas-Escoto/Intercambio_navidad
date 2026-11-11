<?php
/**
 * Archivo: app/core/Migraciones.php
 * Aplica automáticamente los cambios de estructura de la base de datos.
 *
 * ¿Por qué? Cuando la app ya está publicada (por ejemplo en InfinityFree) y tiene datos,
 * no conviene borrar y volver a importar el SQL. En cada petición se revisa (con una
 * consulta muy ligera) si falta alguna migración; si falta, se aplica una sola vez y se
 * anota en la tabla `migraciones`.
 */

class Migraciones
{
    /** Versión más reciente. Súbela cuando agregues una migración nueva abajo. */
    const ULTIMA = 2;

    public static function ejecutar(PDO $db): void
    {
        try {
            $hecha = (int)$db->query('SELECT COALESCE(MAX(version), 0) FROM migraciones')->fetchColumn();
        } catch (PDOException $e) {
            // La tabla aún no existe (base creada con una versión anterior del SQL)
            $db->exec('CREATE TABLE IF NOT EXISTS migraciones (
                         version INT UNSIGNED PRIMARY KEY,
                         aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $hecha = 0;
        }
        if ($hecha >= self::ULTIMA) {
            return;   // lo normal: todo al día
        }

        for ($v = $hecha + 1; $v <= self::ULTIMA; $v++) {
            $metodo = 'v' . $v;
            self::$metodo($db);
            $db->prepare('INSERT IGNORE INTO migraciones (version) VALUES (?)')->execute([$v]);
        }
    }

    /** ¿Existe la columna? (para que cada migración se pueda repetir sin error) */
    private static function hayColumna(PDO $db, string $tabla, string $columna): bool
    {
        $st = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $st->execute([$tabla, $columna]);
        return (int)$st->fetchColumn() > 0;
    }

    /**
     * v1 · Participantes sin cuenta.
     * Agrega nombre, email, foto y token a `participantes`, hace opcional usuario_id,
     * copia los datos de las cuentas existentes y genera un enlace personal para cada uno.
     */
    private static function v1(PDO $db): void
    {
        if (!self::hayColumna($db, 'participantes', 'token')) {
            // usuario_id tiene llave foránea: algunas versiones de MariaDB no dejan cambiarla si están activas
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            $db->exec('ALTER TABLE participantes
                         MODIFY usuario_id INT UNSIGNED NULL,
                         ADD nombre VARCHAR(120) NULL AFTER usuario_id,
                         ADD email  VARCHAR(150) NULL AFTER nombre,
                         ADD foto   VARCHAR(255) NULL AFTER email,
                         ADD token  CHAR(64)     NULL AFTER foto');
            $db->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Copia nombre, correo y foto de la cuenta de cada participante existente
        $db->exec('UPDATE participantes p JOIN usuarios u ON u.id = p.usuario_id
                   SET p.nombre = COALESCE(p.nombre, u.nombre),
                       p.email  = COALESCE(p.email, u.email),
                       p.foto   = COALESCE(p.foto, u.foto)');
        $db->exec("UPDATE participantes SET nombre = 'Participante' WHERE nombre IS NULL");

        // Un token aleatorio (enlace personal) para cada participante que no tenga
        $ids = $db->query('SELECT id FROM participantes WHERE token IS NULL')->fetchAll(PDO::FETCH_COLUMN);
        $st = $db->prepare('UPDATE participantes SET token = ? WHERE id = ?');
        foreach ($ids as $id) {
            $st->execute([bin2hex(random_bytes(32)), $id]);
        }

        $db->exec('ALTER TABLE participantes MODIFY nombre VARCHAR(120) NOT NULL, MODIFY token CHAR(64) NOT NULL');
        $hayIndice = $db->query("SHOW INDEX FROM participantes WHERE Key_name = 'uq_participante_token'")->fetch();
        if (!$hayIndice) {
            $db->exec('ALTER TABLE participantes ADD UNIQUE KEY uq_participante_token (token)');
        }
    }

    /**
     * v2 · Código personal corto para volver a entrar (/entrar), además del enlace personal.
     */
    private static function v2(PDO $db): void
    {
        if (!self::hayColumna($db, 'participantes', 'codigo')) {
            $db->exec('ALTER TABLE participantes ADD codigo CHAR(8) NULL AFTER token');
        }
        require_once RUTA_APP . '/services/CodigoService.php';
        $ids = $db->query('SELECT id FROM participantes WHERE codigo IS NULL')->fetchAll(PDO::FETCH_COLUMN);
        $st = $db->prepare('UPDATE participantes SET codigo = ? WHERE id = ?');
        foreach ($ids as $id) {
            $st->execute([CodigoService::codigoPersonalUnico($db), $id]);
        }
        $db->exec('ALTER TABLE participantes MODIFY codigo CHAR(8) NOT NULL');
        $hayIndice = $db->query("SHOW INDEX FROM participantes WHERE Key_name = 'uq_participante_codigo'")->fetch();
        if (!$hayIndice) {
            $db->exec('ALTER TABLE participantes ADD UNIQUE KEY uq_participante_codigo (codigo)');
        }
    }
}

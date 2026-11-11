<?php
/**
 * Archivo: app/models/Usuario.php
 * Consultas a la tabla `usuarios`. Siempre con consultas preparadas.
 */

class Usuario
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE email = ? LIMIT 1';
        $st = $this->db->prepare($sql);
        $st->execute([mb_strtolower($email)]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->db->prepare('SELECT id, nombre, email, foto, creado_en FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** Crea el usuario y devuelve su nuevo ID. La contraseña se guarda con hash. */
    public function crear(string $nombre, string $email, string $password): int
    {
        $sql = 'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)';
        $st = $this->db->prepare($sql);
        $st->execute([
            $nombre,
            mb_strtolower($email),
            password_hash($password, PASSWORD_DEFAULT),   // bcrypt: nunca se puede "desencriptar"
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Cambia la foto de perfil (nombre del archivo en public/uploads o null). */
    public function actualizarFoto(int $id, ?string $foto): void
    {
        $st = $this->db->prepare('UPDATE usuarios SET foto = ? WHERE id = ?');
        $st->execute([$foto, $id]);
    }

    /** Si PHP mejora su algoritmo de hash, actualizamos el hash guardado. */
    public function actualizarHash(int $id, string $password): void
    {
        $st = $this->db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $st->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}

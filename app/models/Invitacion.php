<?php
/**
 * Archivo: app/models/Invitacion.php
 * Invitaciones personales: cada una tiene un token secreto y su propio enlace.
 */

class Invitacion
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Crea la invitación y devuelve el token (64 caracteres aleatorios). */
    public function crear(int $intercambioId, ?string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $st = $this->db->prepare('INSERT INTO invitaciones (intercambio_id, email, token) VALUES (?, ?, ?)');
        $st->execute([$intercambioId, $email ? mb_strtolower($email) : null, $token]);
        return $token;
    }

    public function listar(int $intercambioId): array
    {
        $st = $this->db->prepare('SELECT * FROM invitaciones WHERE intercambio_id = ? ORDER BY creado_en DESC');
        $st->execute([$intercambioId]);
        return $st->fetchAll();
    }

    /** ¿Ya hay una invitación pendiente para ese correo? (evita duplicados) */
    public function pendientePorEmail(int $intercambioId, string $email): ?array
    {
        $st = $this->db->prepare('SELECT * FROM invitaciones
                                  WHERE intercambio_id = ? AND email = ? AND aceptada_en IS NULL LIMIT 1');
        $st->execute([$intercambioId, mb_strtolower($email)]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function buscarPorToken(string $token): ?array
    {
        $st = $this->db->prepare('SELECT * FROM invitaciones WHERE token = ?');
        $st->execute([$token]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function marcarAceptada(int $id): void
    {
        $st = $this->db->prepare('UPDATE invitaciones SET aceptada_en = NOW() WHERE id = ? AND aceptada_en IS NULL');
        $st->execute([$id]);
    }

    /** Borra la invitación SOLO si pertenece a ese intercambio. */
    public function eliminar(int $id, int $intercambioId): void
    {
        $st = $this->db->prepare('DELETE FROM invitaciones WHERE id = ? AND intercambio_id = ?');
        $st->execute([$id, $intercambioId]);
    }
}

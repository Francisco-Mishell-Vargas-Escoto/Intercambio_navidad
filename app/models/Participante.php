<?php
/**
 * Archivo: app/models/Participante.php
 * Consultas a la tabla `participantes`.
 */

class Participante
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Une a un usuario a un intercambio. Devuelve el ID del participante. */
    public function unir(int $intercambioId, int $usuarioId): int
    {
        // INSERT IGNORE: si ya estaba (UNIQUE intercambio+usuario) no truena ni duplica
        $st = $this->db->prepare('INSERT IGNORE INTO participantes (intercambio_id, usuario_id) VALUES (?, ?)');
        $st->execute([$intercambioId, $usuarioId]);
        $p = $this->buscar($intercambioId, $usuarioId);
        return (int)$p['id'];
    }

    /** Guarda gustos, intereses, lo que no le gusta, preferencias y comentarios. */
    public function actualizarPerfil(int $participanteId, array $d): void
    {
        $sql = 'UPDATE participantes
                SET gustos = ?, intereses = ?, no_le_gusta = ?, preferencias = ?, comentarios = ?
                WHERE id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$d['gustos'], $d['intereses'], $d['no_le_gusta'], $d['preferencias'], $d['comentarios'], $participanteId]);
    }

    /** Marca (1) o desmarca (0) la confirmación de participación. */
    public function confirmar(int $participanteId, bool $confirmado): void
    {
        $st = $this->db->prepare('UPDATE participantes SET confirmado = ? WHERE id = ?');
        $st->execute([$confirmado ? 1 : 0, $participanteId]);
    }

    /** Lista completa para el panel de administración (incluye correo y número de deseos). */
    public function listarDetalle(int $intercambioId): array
    {
        $sql = 'SELECT p.*, u.nombre, u.email, u.foto,
                       (SELECT COUNT(*) FROM deseos d WHERE d.participante_id = p.id) AS total_deseos
                FROM participantes p
                JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.intercambio_id = ?
                ORDER BY u.nombre';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId]);
        return $st->fetchAll();
    }

    /** Busca un participante por su ID, SOLO dentro del intercambio indicado. */
    public function buscarEnIntercambio(int $participanteId, int $intercambioId): ?array
    {
        $sql = 'SELECT p.*, u.nombre, u.email, u.foto
                FROM participantes p JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.id = ? AND p.intercambio_id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$participanteId, $intercambioId]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** Elimina al participante; por las llaves foráneas se borran también sus deseos y restricciones. */
    public function eliminar(int $participanteId): void
    {
        $st = $this->db->prepare('DELETE FROM participantes WHERE id = ?');
        $st->execute([$participanteId]);
    }

    /** Fila del participante (o null si el usuario no pertenece al intercambio). */
    public function buscar(int $intercambioId, int $usuarioId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM participantes WHERE intercambio_id = ? AND usuario_id = ?');
        $st->execute([$intercambioId, $usuarioId]);
        $fila = $st->fetch();
        return $fila ?: null;
    }
}

<?php
/**
 * Archivo: app/models/Deseo.php
 * Consultas a la tabla `deseos` (lista de deseos de cada participante).
 */

class Deseo
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Deseos de un participante, primero los de prioridad alta. */
    public function listar(int $participanteId): array
    {
        $st = $this->db->prepare('SELECT * FROM deseos WHERE participante_id = ? ORDER BY prioridad ASC, id ASC');
        $st->execute([$participanteId]);
        return $st->fetchAll();
    }

    public function contar(int $participanteId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM deseos WHERE participante_id = ?');
        $st->execute([$participanteId]);
        return (int)$st->fetchColumn();
    }

    public function crear(int $participanteId, array $d): void
    {
        $st = $this->db->prepare(
            'INSERT INTO deseos (participante_id, titulo, descripcion, enlace, imagen, prioridad) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$participanteId, $d['titulo'], $d['descripcion'], $d['enlace'], $d['imagen'], $d['prioridad']]);
    }

    /**
     * Busca un deseo SOLO si pertenece al participante indicado.
     * Así nadie puede editar o borrar deseos ajenos cambiando el número en la URL.
     */
    public function buscarDeParticipante(int $deseoId, int $participanteId): ?array
    {
        $sql = 'SELECT d.*, p.intercambio_id
                FROM deseos d
                JOIN participantes p ON p.id = d.participante_id
                WHERE d.id = ? AND d.participante_id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$deseoId, $participanteId]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** Intercambio al que pertenece un deseo (para saber quién es el participante actual). */
    public function intercambioDe(int $deseoId): int
    {
        $st = $this->db->prepare('SELECT p.intercambio_id FROM deseos d JOIN participantes p ON p.id = d.participante_id WHERE d.id = ?');
        $st->execute([$deseoId]);
        return (int)$st->fetchColumn();
    }

    public function actualizar(int $deseoId, array $d): void
    {
        $st = $this->db->prepare(
            'UPDATE deseos SET titulo = ?, descripcion = ?, enlace = ?, imagen = ?, prioridad = ? WHERE id = ?'
        );
        $st->execute([$d['titulo'], $d['descripcion'], $d['enlace'], $d['imagen'], $d['prioridad'], $deseoId]);
    }

    public function eliminar(int $deseoId): void
    {
        $st = $this->db->prepare('DELETE FROM deseos WHERE id = ?');
        $st->execute([$deseoId]);
    }
}

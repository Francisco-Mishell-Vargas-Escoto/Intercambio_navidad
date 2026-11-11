<?php
/**
 * Archivo: app/models/Asignacion.php
 * Resultado del sorteo. Información PRIVADA:
 * no existe ninguna consulta que devuelva la lista completa con nombres.
 * Cada participante solo puede obtener SU propia asignación (deDador).
 */

class Asignacion
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Guarda todas las asignaciones. Se llama DENTRO de la transacción del sorteo. */
    public function guardarTodas(int $intercambioId, array $resultado): void
    {
        $st = $this->db->prepare('INSERT INTO asignaciones (intercambio_id, dador_id, receptor_id) VALUES (?, ?, ?)');
        foreach ($resultado as $dador => $receptor) {
            $st->execute([$intercambioId, $dador, $receptor]);
        }
    }

    public function borrarTodas(int $intercambioId): void
    {
        $st = $this->db->prepare('DELETE FROM asignaciones WHERE intercambio_id = ?');
        $st->execute([$intercambioId]);
    }

    /** Solo el número (el administrador ve cuántas hay, nunca quién con quién). */
    public function contar(int $intercambioId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM asignaciones WHERE intercambio_id = ?');
        $st->execute([$intercambioId]);
        return (int)$st->fetchColumn();
    }

    /** Cuántos participantes ya vieron a su amigo secreto. */
    public function contarVistas(int $intercambioId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM asignaciones WHERE intercambio_id = ? AND visto_en IS NOT NULL');
        $st->execute([$intercambioId]);
        return (int)$st->fetchColumn();
    }

    /**
     * La asignación de UN dador: a quién le regala.
     * El WHERE por dador_id es lo que impide ver asignaciones ajenas.
     */
    public function deDador(int $intercambioId, int $dadorId): ?array
    {
        $sql = 'SELECT a.*, p.nombre AS receptor_nombre, p.foto AS receptor_foto
                FROM asignaciones a
                JOIN participantes p ON p.id = a.receptor_id
                WHERE a.intercambio_id = ? AND a.dador_id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId, $dadorId]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function marcarVista(int $id): void
    {
        $st = $this->db->prepare('UPDATE asignaciones SET visto_en = NOW() WHERE id = ? AND visto_en IS NULL');
        $st->execute([$id]);
    }
}

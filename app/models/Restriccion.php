<?php
/**
 * Archivo: app/models/Restriccion.php
 * Combinaciones prohibidas: "dador NO puede regalarle a receptor".
 */

class Restriccion
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Lista con los nombres de ambas personas (para el panel). */
    public function listar(int $intercambioId): array
    {
        $sql = 'SELECT r.*, pd.nombre AS dador_nombre, pr.nombre AS receptor_nombre
                FROM restricciones r
                JOIN participantes pd ON pd.id = r.dador_id
                JOIN participantes pr ON pr.id = r.receptor_id
                WHERE r.intercambio_id = ?
                ORDER BY pd.nombre, pr.nombre';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId]);
        return $st->fetchAll();
    }

    /**
     * Pares [dador, receptor] listos para el algoritmo.
     * Las bidireccionales generan dos pares: A→B y B→A.
     */
    public function pares(int $intercambioId): array
    {
        $st = $this->db->prepare('SELECT dador_id, receptor_id, bidireccional FROM restricciones WHERE intercambio_id = ?');
        $st->execute([$intercambioId]);
        $pares = [];
        foreach ($st->fetchAll() as $r) {
            $pares[] = [(int)$r['dador_id'], (int)$r['receptor_id']];
            if ($r['bidireccional']) {
                $pares[] = [(int)$r['receptor_id'], (int)$r['dador_id']];
            }
        }
        return $pares;
    }

    /** ¿Ya existe esta restricción (en cualquier sentido si alguna es bidireccional)? */
    public function existe(int $intercambioId, int $a, int $b): bool
    {
        $sql = 'SELECT COUNT(*) FROM restricciones
                WHERE intercambio_id = ?
                  AND ((dador_id = ? AND receptor_id = ?) OR (dador_id = ? AND receptor_id = ? AND bidireccional = 1))';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId, $a, $b, $b, $a]);
        return (int)$st->fetchColumn() > 0;
    }

    public function crear(int $intercambioId, int $dador, int $receptor, bool $bidireccional): void
    {
        $st = $this->db->prepare('INSERT INTO restricciones (intercambio_id, dador_id, receptor_id, bidireccional) VALUES (?, ?, ?, ?)');
        $st->execute([$intercambioId, $dador, $receptor, $bidireccional ? 1 : 0]);
    }

    /** Borra SOLO si pertenece a ese intercambio. */
    public function eliminar(int $id, int $intercambioId): void
    {
        $st = $this->db->prepare('DELETE FROM restricciones WHERE id = ? AND intercambio_id = ?');
        $st->execute([$id, $intercambioId]);
    }
}

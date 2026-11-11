<?php
/**
 * Archivo: app/models/Intercambio.php
 * Consultas a la tabla `intercambios`.
 */

class Intercambio
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /** Inserta un intercambio y devuelve su ID. $d ya viene validado. */
    public function crear(array $d): int
    {
        $sql = 'INSERT INTO intercambios
                  (admin_id, nombre, descripcion, fecha_evento, fecha_limite,
                   presupuesto_min, presupuesto_max, lugar, reglas, codigo_acceso)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $st = $this->db->prepare($sql);
        $st->execute([
            $d['admin_id'], $d['nombre'], $d['descripcion'], $d['fecha_evento'], $d['fecha_limite'],
            $d['presupuesto_min'], $d['presupuesto_max'], $d['lugar'], $d['reglas'], $d['codigo_acceso'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Busca por ID e incluye el número de participantes y el nombre del admin. */
    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT i.*, u.nombre AS admin_nombre,
                       (SELECT COUNT(*) FROM participantes p WHERE p.intercambio_id = i.id) AS total_participantes
                FROM intercambios i
                JOIN usuarios u ON u.id = i.admin_id
                WHERE i.id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$id]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        $st = $this->db->prepare('SELECT id FROM intercambios WHERE codigo_acceso = ?');
        $st->execute([$codigo]);
        $id = $st->fetchColumn();
        return $id ? $this->buscarPorId((int)$id) : null;
    }

    /**
     * Intercambios donde el usuario es administrador O participante.
     * es_admin y es_participante indican su rol en cada uno.
     */
    public function listarDeUsuario(int $usuarioId): array
    {
        $sql = 'SELECT i.*,
                       (i.admin_id = :u1) AS es_admin,
                       EXISTS(SELECT 1 FROM participantes p2
                              WHERE p2.intercambio_id = i.id AND p2.usuario_id = :u2) AS es_participante,
                       (SELECT COUNT(*) FROM participantes p WHERE p.intercambio_id = i.id) AS total_participantes
                FROM intercambios i
                WHERE i.admin_id = :u3
                   OR i.id IN (SELECT intercambio_id FROM participantes WHERE usuario_id = :u4)
                ORDER BY i.fecha_evento ASC';
        $st = $this->db->prepare($sql);
        $st->execute([':u1' => $usuarioId, ':u2' => $usuarioId, ':u3' => $usuarioId, ':u4' => $usuarioId]);
        return $st->fetchAll();
    }

    /** El próximo intercambio (que aún no pasa) del usuario, para la página de inicio. */
    public function proximoDeUsuario(int $usuarioId): ?array
    {
        foreach ($this->listarDeUsuario($usuarioId) as $i) {
            if (strtotime($i['fecha_evento']) >= time()) {
                return $i;
            }
        }
        return null;
    }

    /** Nombres de los participantes (visibles para los miembros del intercambio). */
    public function nombresParticipantes(int $intercambioId): array
    {
        $sql = 'SELECT u.nombre, u.foto, p.confirmado,
                       (SELECT COUNT(*) FROM deseos d WHERE d.participante_id = p.id) AS total_deseos
                FROM participantes p JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.intercambio_id = ?
                ORDER BY u.nombre';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId]);
        return $st->fetchAll();
    }

    /** Actualiza los datos generales (Configuración del panel de administración). */
    public function actualizar(int $id, array $d): void
    {
        $sql = 'UPDATE intercambios
                SET nombre = ?, descripcion = ?, fecha_evento = ?, fecha_limite = ?,
                    presupuesto_min = ?, presupuesto_max = ?, lugar = ?, reglas = ?
                WHERE id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$d['nombre'], $d['descripcion'], $d['fecha_evento'], $d['fecha_limite'],
                      $d['presupuesto_min'], $d['presupuesto_max'], $d['lugar'], $d['reglas'], $id]);
    }

    /**
     * Cambia el estado solo si el estado actual es $desde.
     * Devuelve true si se cambió (evita cambios dobles o fuera de orden).
     */
    public function cambiarEstado(int $id, string $desde, string $hacia): bool
    {
        $st = $this->db->prepare('UPDATE intercambios SET estado = ? WHERE id = ? AND estado = ?');
        $st->execute([$hacia, $id, $desde]);
        return $st->rowCount() === 1;
    }

    /**
     * Lee el estado y BLOQUEA la fila hasta que termine la transacción (SELECT ... FOR UPDATE).
     * Si dos peticiones intentan sortear al mismo tiempo, la segunda espera a que la
     * primera termine y entonces ya ve el estado "sorteado": imposible sortear dos veces.
     */
    public function estadoBloqueado(int $id): ?string
    {
        $st = $this->db->prepare('SELECT estado FROM intercambios WHERE id = ? FOR UPDATE');
        $st->execute([$id]);
        $estado = $st->fetchColumn();
        return $estado === false ? null : $estado;
    }

    /** Marca el sorteo como hecho, deshecho o publicado y guarda la fecha. */
    public function registrarSorteo(int $id, string $estado): void
    {
        $sql = [
            'sorteado' => 'UPDATE intercambios SET estado = ?, sorteado_en = NOW() WHERE id = ?',
            'cerrado'  => 'UPDATE intercambios SET estado = ?, sorteado_en = NULL WHERE id = ?',
            'revelado' => 'UPDATE intercambios SET estado = ?, revelado_en = NOW() WHERE id = ?',
        ];
        $st = $this->db->prepare($sql[$estado]);
        $st->execute([$estado, $id]);
    }

    public function cambiarCodigo(int $id, string $codigo): void
    {
        $st = $this->db->prepare('UPDATE intercambios SET codigo_acceso = ? WHERE id = ?');
        $st->execute([$codigo, $id]);
    }

    /** Números para las tarjetas de estadísticas del panel. */
    public function estadisticas(int $id): array
    {
        $sql = 'SELECT
                  COUNT(*)                                               AS participantes,
                  COALESCE(SUM(p.confirmado), 0)                         AS confirmados,
                  COALESCE(SUM(EXISTS(SELECT 1 FROM deseos d WHERE d.participante_id = p.id)), 0) AS con_deseos,
                  COALESCE(SUM(p.gustos IS NOT NULL OR p.intereses IS NOT NULL OR p.no_le_gusta IS NOT NULL), 0) AS con_perfil,
                  (SELECT COUNT(*) FROM deseos d JOIN participantes p2 ON p2.id = d.participante_id
                    WHERE p2.intercambio_id = :i1)                      AS total_deseos
                FROM participantes p
                WHERE p.intercambio_id = :i2';
        $st = $this->db->prepare($sql);
        $st->execute([':i1' => $id, ':i2' => $id]);
        $e = array_map('intval', $st->fetch());

        $st = $this->db->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(aceptada_en IS NOT NULL), 0) AS aceptadas
                                  FROM invitaciones WHERE intercambio_id = ?');
        $st->execute([$id]);
        $inv = $st->fetch();
        $e['invitaciones'] = (int)$inv['total'];
        $e['invitaciones_aceptadas'] = (int)$inv['aceptadas'];
        return $e;
    }

    /** ¿Todavía se aceptan registros? Abierto y sin pasar la fecha límite. */
    public static function registroAbierto(array $i): bool
    {
        return $i['estado'] === 'abierto' && strtotime($i['fecha_limite']) >= time();
    }
}

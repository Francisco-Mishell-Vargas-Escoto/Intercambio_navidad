<?php
/**
 * Archivo: app/models/Notificacion.php
 * Avisos dentro de la plataforma (la campanita del menú).
 * tipos: registro, confirmacion, sorteo, revelacion, invitacion, general
 */

class Notificacion
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    public function crear(int $usuarioId, ?int $intercambioId, string $tipo, string $mensaje): void
    {
        $st = $this->db->prepare('INSERT INTO notificaciones (usuario_id, intercambio_id, tipo, mensaje) VALUES (?, ?, ?, ?)');
        $st->execute([$usuarioId, $intercambioId, $tipo, mb_substr($mensaje, 0, 255)]);
    }

    /** Envía el mismo aviso a todos los participantes de un intercambio (menos, opcionalmente, a uno). */
    public function crearParaParticipantes(int $intercambioId, string $tipo, string $mensaje, int $excepto = 0): void
    {
        $sql = 'INSERT INTO notificaciones (usuario_id, intercambio_id, tipo, mensaje)
                SELECT usuario_id, ?, ?, ? FROM participantes WHERE intercambio_id = ? AND usuario_id <> ?';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId, $tipo, mb_substr($mensaje, 0, 255), $intercambioId, $excepto]);
    }

    public function listar(int $usuarioId, int $limite = 50): array
    {
        $st = $this->db->prepare('SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY creado_en DESC, id DESC LIMIT ' . (int)$limite);
        $st->execute([$usuarioId]);
        return $st->fetchAll();
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0');
        $st->execute([$usuarioId]);
        return (int)$st->fetchColumn();
    }

    /** Busca una notificación SOLO si es del usuario. */
    public function buscarDeUsuario(int $id, int $usuarioId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM notificaciones WHERE id = ? AND usuario_id = ?');
        $st->execute([$id, $usuarioId]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function marcarLeida(int $id, int $usuarioId): void
    {
        $st = $this->db->prepare('UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?');
        $st->execute([$id, $usuarioId]);
    }

    public function marcarTodas(int $usuarioId): void
    {
        $st = $this->db->prepare('UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0');
        $st->execute([$usuarioId]);
    }
}

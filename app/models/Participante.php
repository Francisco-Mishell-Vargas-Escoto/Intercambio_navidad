<?php
/**
 * Archivo: app/models/Participante.php
 * Consultas a la tabla `participantes`.
 * Un participante puede no tener cuenta: se identifica con su token (enlace personal)
 * o con su código personal corto.
 */

require_once RUTA_APP . '/services/CodigoService.php';

class Participante
{
    private $db;

    public function __construct()
    {
        $this->db = Database::conectar();
    }

    /**
     * Crea un participante SIN cuenta (invitado). Devuelve la fila completa.
     * $usuarioId solo se pasa si la persona tiene cuenta (por ejemplo, el organizador).
     */
    public function crear(int $intercambioId, string $nombre, ?string $email, ?int $usuarioId = null): array
    {
        $token = bin2hex(random_bytes(32));                       // enlace personal: 64 caracteres
        $codigo = CodigoService::codigoPersonalUnico($this->db);  // código personal: 8 caracteres
        $st = $this->db->prepare('INSERT INTO participantes (intercambio_id, usuario_id, nombre, email, token, codigo) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$intercambioId, $usuarioId, $nombre, $email ? mb_strtolower($email) : null, $token, $codigo]);
        return $this->buscarPorToken($token);
    }

    /** Une a una persona CON cuenta (si ya estaba, devuelve su participación). */
    public function unirCuenta(int $intercambioId, array $usuario): array
    {
        $ya = $this->buscarPorUsuario($intercambioId, (int)$usuario['id']);
        return $ya ?: $this->crear($intercambioId, $usuario['nombre'], $usuario['email'], (int)$usuario['id']);
    }

    public function buscarPorToken(string $token): ?array
    {
        $st = $this->db->prepare('SELECT * FROM participantes WHERE token = ?');
        $st->execute([$token]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** Busca por código personal (ya normalizado: mayúsculas, sin guiones). */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $st = $this->db->prepare('SELECT * FROM participantes WHERE codigo = ?');
        $st->execute([$codigo]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** Participación de una CUENTA en un intercambio (o null). */
    public function buscarPorUsuario(int $intercambioId, int $usuarioId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM participantes WHERE intercambio_id = ? AND usuario_id = ?');
        $st->execute([$intercambioId, $usuarioId]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** ¿Ya hay alguien con ese nombre en el intercambio? (evita que alguien se registre dos veces) */
    public function existeNombre(int $intercambioId, string $nombre, int $exceptoId = 0): bool
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM participantes WHERE intercambio_id = ? AND LOWER(nombre) = LOWER(?) AND id <> ?');
        $st->execute([$intercambioId, trim($nombre), $exceptoId]);
        return (int)$st->fetchColumn() > 0;
    }

    /** Guarda nombre, gustos, intereses, lo que no le gusta, preferencias y comentarios. */
    public function actualizarPerfil(int $participanteId, array $d): void
    {
        $sql = 'UPDATE participantes
                SET nombre = ?, gustos = ?, intereses = ?, no_le_gusta = ?, preferencias = ?, comentarios = ?
                WHERE id = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$d['nombre'], $d['gustos'], $d['intereses'], $d['no_le_gusta'], $d['preferencias'], $d['comentarios'], $participanteId]);
    }

    /** Cambia la foto del participante (archivo en public/uploads o null). */
    public function actualizarFoto(int $participanteId, ?string $foto): void
    {
        $st = $this->db->prepare('UPDATE participantes SET foto = ? WHERE id = ?');
        $st->execute([$foto, $participanteId]);
    }

    /** Marca (1) o desmarca (0) la confirmación de participación. */
    public function confirmar(int $participanteId, bool $confirmado): void
    {
        $st = $this->db->prepare('UPDATE participantes SET confirmado = ? WHERE id = ?');
        $st->execute([$confirmado ? 1 : 0, $participanteId]);
    }

    /**
     * Genera un enlace personal y un código personal NUEVOS: los anteriores dejan de funcionar.
     * Devuelve el token nuevo.
     */
    public function regenerarToken(int $participanteId): string
    {
        $token = bin2hex(random_bytes(32));
        $codigo = CodigoService::codigoPersonalUnico($this->db);
        $st = $this->db->prepare('UPDATE participantes SET token = ?, codigo = ? WHERE id = ?');
        $st->execute([$token, $codigo, $participanteId]);
        return $token;
    }

    /** Lista completa para el panel de administración (incluye número de deseos). */
    public function listarDetalle(int $intercambioId): array
    {
        $sql = 'SELECT p.*,
                       (SELECT COUNT(*) FROM deseos d WHERE d.participante_id = p.id) AS total_deseos
                FROM participantes p
                WHERE p.intercambio_id = ?
                ORDER BY p.nombre';
        $st = $this->db->prepare($sql);
        $st->execute([$intercambioId]);
        return $st->fetchAll();
    }

    /** Busca un participante por su ID, SOLO dentro del intercambio indicado. */
    public function buscarEnIntercambio(int $participanteId, int $intercambioId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM participantes WHERE id = ? AND intercambio_id = ?');
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
}

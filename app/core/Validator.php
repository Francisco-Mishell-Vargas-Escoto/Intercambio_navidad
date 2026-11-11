<?php
/**
 * Archivo: app/core/Validator.php
 * Validación de formularios en el servidor (backend).
 *
 * Uso:
 *   $v = new Validator($_POST);
 *   $v->requerido('nombre', 'El nombre')->max('nombre', 120, 'El nombre');
 *   if ($v->falla()) { $errores = $v->errores(); }
 */

class Validator
{
    private $datos;
    private $errores = [];

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /** Valor limpio (sin espacios al inicio y al final). */
    public function valor(string $campo): string
    {
        $v = $this->datos[$campo] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    private function agregar(string $campo, string $mensaje): void
    {
        // Solo guardamos el primer error de cada campo
        if (!isset($this->errores[$campo])) {
            $this->errores[$campo] = $mensaje;
        }
    }

    public function requerido(string $campo, string $etiqueta): self
    {
        if ($this->valor($campo) === '') {
            $this->agregar($campo, "$etiqueta es obligatorio.");
        }
        return $this;
    }

    public function email(string $campo): self
    {
        $v = $this->valor($campo);
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->agregar($campo, 'Escribe un correo válido, por ejemplo nombre@correo.com.');
        }
        return $this;
    }

    public function min(string $campo, int $min, string $etiqueta): self
    {
        $v = $this->valor($campo);
        if ($v !== '' && mb_strlen($v) < $min) {
            $this->agregar($campo, "$etiqueta debe tener al menos $min caracteres.");
        }
        return $this;
    }

    public function max(string $campo, int $max, string $etiqueta): self
    {
        if (mb_strlen($this->valor($campo)) > $max) {
            $this->agregar($campo, "$etiqueta no puede pasar de $max caracteres.");
        }
        return $this;
    }

    /** Compara dos campos (ej. password y confirmar_password). Sin trim, para contraseñas. */
    public function igual(string $campo, string $otro, string $mensaje): self
    {
        if (($this->datos[$campo] ?? '') !== ($this->datos[$otro] ?? '')) {
            $this->agregar($otro, $mensaje);
        }
        return $this;
    }

    /** Contraseña segura: mínimo 8 caracteres, al menos una letra y un número. */
    public function password(string $campo): self
    {
        $v = $this->datos[$campo] ?? '';
        if (!is_string($v) || strlen($v) < 8 || !preg_match('/[A-Za-z]/', $v) || !preg_match('/\d/', $v)) {
            $this->agregar($campo, 'La contraseña debe tener al menos 8 caracteres, con letras y números.');
        }
        return $this;
    }

    /** Para agregar errores desde el controlador (ej. "el correo ya existe"). */
    public function error(string $campo, string $mensaje): self
    {
        $this->agregar($campo, $mensaje);
        return $this;
    }

    public function falla(): bool
    {
        return !empty($this->errores);
    }

    public function errores(): array
    {
        return $this->errores;
    }
}

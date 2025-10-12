<?php
class Validator {
    private $errors = [];

    public function validatePhone($phone) {
        if (empty($phone)) {
            return true; // El teléfono es opcional
        }

        // Formato: +XX XXX XXX XXX o similar
        $pattern = '/^\+?[0-9]{1,4}[-\s]?[0-9]{3}[-\s]?[0-9]{3}[-\s]?[0-9]{3}$/';
        if (!preg_match($pattern, $phone)) {
            $this->errors[] = "El formato del teléfono no es válido. Use el formato: +34 123 456 789";
            return false;
        }
        return true;
    }

    public function validateEmail($email) {
        if (empty($email)) {
            $this->errors[] = "El email es obligatorio";
            return false;
        }

        // Validación básica de formato
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "El formato del email no es válido";
            return false;
        }

        // Validación de dominio
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, "MX")) {
            $this->errors[] = "El dominio del email no parece ser válido";
            return false;
        }

        return true;
    }

    public function validatePassword($password, $confirm = null) {
        if (empty($password)) {
            $this->errors[] = "La contraseña es obligatoria";
            return false;
        }

        // Longitud mínima
        if (strlen($password) < 8) {
            $this->errors[] = "La contraseña debe tener al menos 8 caracteres";
            return false;
        }

        // Debe contener al menos una mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors[] = "La contraseña debe contener al menos una letra mayúscula";
            return false;
        }

        // Debe contener al menos una minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $this->errors[] = "La contraseña debe contener al menos una letra minúscula";
            return false;
        }

        // Debe contener al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            $this->errors[] = "La contraseña debe contener al menos un número";
            return false;
        }

        // Debe contener al menos un carácter especial
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $this->errors[] = "La contraseña debe contener al menos un carácter especial (!@#$%^&*()-_=+{};:,<.>)";
            return false;
        }

        // Si se proporciona confirmación, verificar que coincidan
        if ($confirm !== null && $password !== $confirm) {
            $this->errors[] = "Las contraseñas no coinciden";
            return false;
        }

        return true;
    }

    public function validateUsername($username) {
        if (empty($username)) {
            $this->errors[] = "El nombre de usuario es obligatorio";
            return false;
        }

        // Longitud entre 3 y 20 caracteres
        if (strlen($username) < 3 || strlen($username) > 20) {
            $this->errors[] = "El nombre de usuario debe tener entre 3 y 20 caracteres";
            return false;
        }

        // Solo letras, números y guiones bajos
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->errors[] = "El nombre de usuario solo puede contener letras, números y guiones bajos";
            return false;
        }

        return true;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function clearErrors() {
        $this->errors = [];
    }
}
?>
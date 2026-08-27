<?php
declare(strict_types=1);

namespace App\Validators;

class Validator
{
    protected array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return is_array($fieldErrors) ? $fieldErrors[0] : $fieldErrors;
            }
        }
        return null;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function validateRequired(array $data, array $fields): void
    {
        foreach ($fields as $field => $label) {
            $val = trim((string)($data[$field] ?? ''));
            if ($val === '') {
                $this->addError($field, "The {$label} field is required.");
            }
        }
    }

    public function validateEmail(string $email, string $field = 'email'): void
    {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Please provide a valid email address.");
        }
    }

    public function validateDate(string $date, string $field, string $label): void
    {
        if ($date !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                $this->addError($field, "The {$label} must be a valid date format (YYYY-MM-DD).");
            }
        }
    }

    public function validateNumeric(mixed $value, string $field, string $label, float $min = 0): void
    {
        if ($value !== null && $value !== '') {
            if (!is_numeric($value) || (float)$value < $min) {
                $this->addError($field, "The {$label} must be a valid number greater than or equal to {$min}.");
            }
        }
    }
}

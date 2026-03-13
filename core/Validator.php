<?php
/**
 * Core\Validator - Валидация данных
 */

namespace Core;

class Validator {
    private $data = [];
    private $errors = [];
    private $rules = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public static function make(array $data): self {
        return new self($data);
    }

    public function validate(array $rules): bool {
        $this->rules = $rules;
        $this->errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            
            foreach ($ruleList as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }

    private function applyRule(string $field, string $rule): void {
        // Парсим правило и параметры
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];
        
        $value = $this->data[$field] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "Поле '$field' обязательно для заполнения");
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Неверный формат email");
                }
                break;
                
            case 'min':
                $min = (int)$parameters[0];
                if ($value && strlen($value) < $min) {
                    $this->addError($field, "Минимальная длина: $min символов");
                }
                break;
                
            case 'max':
                $max = (int)$parameters[0];
                if ($value && strlen($value) > $max) {
                    $this->addError($field, "Максимальная длина: $max символов");
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "Поле '$field' должно быть числом");
                }
                break;
                
            case 'integer':
                if ($value && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "Поле '$field' должно быть целым числом");
                }
                break;
                
            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "Неверный формат URL");
                }
                break;
                
            case 'in':
                if ($value && !in_array($value, $parameters)) {
                    $this->addError($field, "Недопустимое значение");
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value && (!isset($this->data[$confirmField]) || $value !== $this->data[$confirmField])) {
                    $this->addError($field, "Поля не совпадают");
                }
                break;
                
            case 'unique':
                // Проверка уникальности в БД
                if ($value && isset($parameters[0])) {
                    $table = $parameters[0];
                    $column = $parameters[1] ?? $field;
                    $except = $parameters[2] ?? null;
                    
                    $db = Database::getInstance();
                    $where = "$column = ?";
                    $params = [$value];
                    
                    if ($except) {
                        $where .= " AND id != ?";
                        $params[] = $except;
                    }
                    
                    if ($db->exists($table, $where, $params)) {
                        $this->addError($field, "Значение уже существует");
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function errors(): array {
        return $this->errors;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function firstError(string $field = null): ?string {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        
        return null;
    }

    public function validated(): array {
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }
}

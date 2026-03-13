<?php
/**
 * Core\Validator — валидация входных данных
 * Использование:
 *   $v = Validator::make($_POST);
 *   if ($v->validate(['email' => 'required|email', 'name' => 'required|min:3|max:100'])) {
 *       $data = $v->validated();
 *   } else {
 *       $errors = $v->errors();
 *   }
 */
class Validator
{
    private array $data;
    private array $errors  = [];
    private array $validated = [];

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Запустить валидацию
     * @param array $rules  ['field' => 'rule1|rule2:arg', ...]
     */
    public function validate(array $rules): bool
    {
        $this->errors    = [];
        $this->validated = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value      = $this->data[$field] ?? null;
            $valid      = true;

            foreach ($fieldRules as $rule) {
                [$ruleName, $ruleArg] = array_pad(explode(':', $rule, 2), 2, null);

                $error = $this->applyRule($field, $value, $ruleName, $ruleArg);
                if ($error !== null) {
                    $this->errors[$field][] = $error;
                    $valid = false;
                    break; // Прекращаем проверку поля при первой ошибке
                }
            }

            if ($valid && $value !== null) {
                $this->validated[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $arg): ?string
    {
        $label = ucfirst($field);

        return match($rule) {
            'required' => (($value === null || $value === '' || (is_string($value) && trim($value) === ''))
                ? "Поле {$label} обязательно для заполнения"
                : null),

            'min' => ((mb_strlen((string)($value ?? '')) < (int)$arg)
                ? "Поле {$label} должно быть не менее {$arg} символов"
                : null),

            'max' => ((mb_strlen((string)($value ?? '')) > (int)$arg)
                ? "Поле {$label} не должно превышать {$arg} символов"
                : null),

            'numeric' => (($value !== null && $value !== '' && !is_numeric($value))
                ? "Поле {$label} должно быть числом"
                : null),

            'integer' => (($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false)
                ? "Поле {$label} должно быть целым числом"
                : null),

            'min_val' => (($value !== null && $value !== '' && (float)$value < (float)$arg)
                ? "Поле {$label} должно быть не менее {$arg}"
                : null),

            'max_val' => (($value !== null && $value !== '' && (float)$value > (float)$arg)
                ? "Поле {$label} не должно превышать {$arg}"
                : null),

            'email' => (($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL))
                ? "Поле {$label} должно быть корректным email"
                : null),

            'url' => (($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL))
                ? "Поле {$label} должно быть корректным URL"
                : null),

            'date' => (($value !== null && $value !== '' && !strtotime($value))
                ? "Поле {$label} должно быть корректной датой"
                : null),

            'in' => (($value !== null && $value !== '' && !in_array($value, explode(',', (string)$arg), true))
                ? "Поле {$label} содержит недопустимое значение"
                : null),

            'not_in' => (($value !== null && $value !== '' && in_array($value, explode(',', (string)$arg), true))
                ? "Поле {$label} содержит недопустимое значение"
                : null),

            'regex' => (($value !== null && $value !== '' && !preg_match((string)$arg, (string)$value))
                ? "Поле {$label} имеет неверный формат"
                : null),

            'alpha' => (($value !== null && $value !== '' && !preg_match('/^[a-zA-Zа-яёА-ЯЁ]+$/u', (string)$value))
                ? "Поле {$label} должно содержать только буквы"
                : null),

            'alpha_num' => (($value !== null && $value !== '' && !preg_match('/^[a-zA-Z0-9а-яёА-ЯЁ]+$/u', (string)$value))
                ? "Поле {$label} должно содержать только буквы и цифры"
                : null),

            'username' => (($value !== null && $value !== '' && !preg_match('/^[a-zA-Z0-9_.-]+$/', (string)$value))
                ? "Поле {$label} содержит недопустимые символы"
                : null),

            'password' => (($value !== null && $value !== '' && mb_strlen((string)$value) < 8)
                ? "Пароль должен быть не менее 8 символов"
                : null),

            'confirmed' => (($value !== ($this->data[$field . '_confirmation'] ?? null))
                ? "Поле {$label} не совпадает с подтверждением"
                : null),

            'nullable' => null, // Всегда пропускаем

            default => null,
        };
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Получить первую ошибку как строку для flash
     */
    public function firstErrorMessage(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}

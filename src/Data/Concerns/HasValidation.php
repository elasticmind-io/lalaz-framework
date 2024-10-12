<?php declare(strict_types=1);

namespace Lalaz\Data\Concerns;

use Lalaz\Lalaz;

trait HasValidation
{
    // Validation rule constants
    public const VALIDATE_REQUIRED = 'required';
    public const VALIDATE_INT = 'int';
    public const VALIDATE_DECIMAL = 'decimal';
    public const VALIDATE_BOOL = 'boolean';
    public const VALIDATE_EMAIL = 'email';
    public const VALIDATE_URL = 'url';
    public const VALIDATE_DOMAIN = 'domain';
    public const VALIDATE_IP = 'ip';
    public const VALIDATE_MIN = 'min';
    public const VALIDATE_MAX = 'max';
    public const VALIDATE_UNIQUE = 'unique';
    public const VALIDATE_MATCH = 'match';

    /** @var array $errors An array to store validation errors */
    public array $errors = [];

    /**
     * Builds a new instance of the model with the provided data.
     *
     * @param array $data An associative array of property values to set on the model.
     * @return static A new instance of the model with properties set.
     */
    public static function build($data = array())
    {
        $result = new static();

        foreach ($data as $key => $value) {
            if (property_exists($result, $key)) {
                $result->{$key} = $value;
            }
        }

        return $result;
    }

    /**
     * Returns the default error messages for validation rules.
     *
     * @return array An associative array of validation rules and their corresponding error messages.
     */
    public static function errorMessages()
    {
        return [
            self::VALIDATE_REQUIRED => 'This field is required',
            self::VALIDATE_INT => 'This field must be a number',
            self::VALIDATE_DECIMAL => 'This field must be a decimal',
            self::VALIDATE_BOOL => 'This field must be true or false',
            self::VALIDATE_EMAIL => 'This field must be a valid email address',
            self::VALIDATE_URL => 'This field must be a valid URL',
            self::VALIDATE_DOMAIN => 'This field must be a valid domain',
            self::VALIDATE_IP => 'This field must be a valid IP address',
            self::VALIDATE_MIN => 'Minimum length of this field must be {min}',
            self::VALIDATE_MAX => 'Maximum length of this field must be {max}',
            self::VALIDATE_MATCH => 'This field must match {match}',
            self::VALIDATE_UNIQUE => 'Record with this {field} already exists',
        ];
    }

    /**
     * Defines the validation rules for model attributes.
     *
     * Subclasses should implement this method to specify validation rules.
     *
     * @return array An associative array where keys are attribute names and values are arrays of validation rules.
     */
    protected function validates(): array
    {
        return [];
    }

    /**
     * Retrieves the error message for a given validation rule.
     *
     * @param string $rule The validation rule constant.
     * @return string The error message associated with the rule.
     */
    public function errorMessage($rule)
    {
        return static::errorMessages()[$rule];
    }

    /**
     * Adds an error message to a specific attribute.
     *
     * @param string $attribute The attribute that failed validation.
     * @param string $message The error message to add.
     * @return void
     */
    public function addError(string $attribute, string $message)
    {
        $this->errors[$attribute][] = $message;
    }

    /**
     * Checks if there are any errors for a specific attribute.
     *
     * @param string $attribute The attribute to check for errors.
     * @return bool True if there are errors, false otherwise.
     */
    public function hasError($attribute)
    {
        return isset($this->errors[$attribute]);
    }

    /**
     * Retrieves the first error message for a specific attribute.
     *
     * @param string $attribute The attribute to get the error message for.
     * @return string The first error message, or an empty string if none exist.
     */
    public function getFirstError($attribute)
    {
        $errors = $this->errors[$attribute] ?? [];
        return $errors[0] ?? '';
    }

    /**
     * Adds an error message based on a validation rule and parameters.
     *
     * @param string $attribute The attribute that failed validation.
     * @param string $rule The validation rule constant.
     * @param array  $params Additional parameters for the error message.
     * @return void
     */
    protected function addErrorByRule(string $attribute, string $rule, $params = [])
    {
        $params['field'] ??= $attribute;
        $errorMessage = $this->errorMessage($rule);

        foreach ($params as $key => $value) {
            $errorMessage = str_replace("{{$key}}", $value, $errorMessage);
        }

        $this->errors[$attribute][] = $errorMessage;
    }

    /**
     * Validates the model's attributes based on the defined validation rules.
     *
     * @param string $context The context of validation ('create', 'update', etc.).
     * @return bool True if validation passes without errors, false otherwise.
     */
    public function validate(string $context = 'create'): bool
    {
        foreach ($this->validates() as $attribute => $rules) {
            $value = $this->{$attribute};

            foreach ($rules as $rule) {
                $ruleName = is_array($rule) ? $rule[0] : $rule;

                if (is_array($rule) && isset($rule['on']) && $rule['on'] !== $context) {
                    continue;
                }

                if ($ruleName === self::VALIDATE_REQUIRED && !$value) {
                    $this->addErrorByRule($attribute, self::VALIDATE_REQUIRED);
                }

                if ($ruleName === self::VALIDATE_INT && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addErrorByRule($attribute, self::VALIDATE_INT);
                }

                if ($ruleName === self::VALIDATE_DECIMAL && !filter_var($value, FILTER_VALIDATE_FLOAT)) {
                    $this->addErrorByRule($attribute, self::VALIDATE_DECIMAL);
                }

                if ($ruleName === self::VALIDATE_BOOL) {
                    if (is_null($value)) {
                        $this->addErrorByRule($attribute, self::VALIDATE_REQUIRED);
                    } elseif (!is_bool(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
                        $this->addErrorByRule($attribute, self::VALIDATE_BOOL);
                    }
                }

                if ($ruleName === self::VALIDATE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addErrorByRule($attribute, self::VALIDATE_EMAIL);
                }

                if ($ruleName === self::VALIDATE_URL && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addErrorByRule($attribute, self::VALIDATE_URL);
                }

                if ($ruleName === self::VALIDATE_DOMAIN && !filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    $this->addErrorByRule($attribute, self::VALIDATE_DOMAIN);
                }

                if ($ruleName === self::VALIDATE_IP && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addErrorByRule($attribute, self::VALIDATE_IP);
                }

                if ($ruleName === self::VALIDATE_MIN && strlen($value) < $rule['min']) {
                    $this->addErrorByRule($attribute, self::VALIDATE_MIN, ['min' => $rule['min']]);
                }

                if ($ruleName === self::VALIDATE_MAX && strlen($value) > $rule['max']) {
                    $this->addErrorByRule($attribute, self::VALIDATE_MAX, ['max' => $rule['max']]);
                }

                if ($ruleName === self::VALIDATE_MATCH && $value !== $this->{$rule['match']}) {
                    $this->addErrorByRule($attribute, self::VALIDATE_MATCH, ['match' => $rule['match']]);
                }

                if ($ruleName === self::VALIDATE_UNIQUE) {
                    $target = $rule['class'];
                    $uniqueAttr = $rule['attribute'] ?? $attribute;

                    $tableName = $target->tableName();
                    $pkName = $target->primaryKey();
                    $pkValue = intval($target->{$pkName});

                    $sql = "SELECT * FROM $tableName WHERE $uniqueAttr = :$uniqueAttr";

                    if ($pkValue > 0) {
                        $sql .= " AND $pkName <> :pkValue";
                    }

                    $statement = Lalaz::getInstance()->db->prepare($sql);
                    $statement->bindValue(":$uniqueAttr", $value);
                    if ($pkValue > 0) {
                        $statement->bindValue(":pkValue", $pkValue);
                    }
                    $statement->execute();
                    $record = $statement->fetchObject();

                    if ($record) {
                        $this->addErrorByRule($attribute, self::VALIDATE_UNIQUE, ['field' => $attribute]);
                    }
                }
            }
        }

        return empty($this->errors);
    }
}

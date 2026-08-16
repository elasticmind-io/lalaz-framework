<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Class Expr
 *
 * This class is responsible for building SQL expressions dynamically.
 * It provides methods to construct WHERE clause conditions for SQL queries
 * in a fluent and chainable manner.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Expr
{
    /** @var array $conditions Holds the parts of the SQL condition expressions */
    private array $conditions = [];

    /** @var array $parameters Holds the parameters to bind to the SQL query */
    private array $parameters = [];

    /**
     * Registra um valor e devolve o nome do marcador que o representa.
     *
     * O nome tem que ser UNICO. Antes o parametro era indexado pelo nome da
     * coluna, entao duas condicoes sobre a mesma coluna geravam dois marcadores
     * iguais e um valor so — a segunda sobrescrevia a primeira em silencio, e um
     * intervalo virava "created_at > X AND created_at < X", que nunca casa nada.
     *
     * O nome tambem e limpo de tudo que nao for palavra: uma coluna qualificada
     * como "posts.slug" produzia ":posts.slug", que o PDO nao aceita.
     *
     * A primeira ocorrencia mantem o nome simples, entao a consulta gerada para
     * o caso comum continua identica a de antes.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to bind.
     * @return string The placeholder name, without the leading colon.
     */
    private function bind(string $key, mixed $value): string
    {
        $base = preg_replace('/[^A-Za-z0-9_]/', '_', $key);

        if ($base === '' || is_numeric($base[0])) {
            $base = 'p_' . $base;
        }

        $name = $base;
        $suffix = 1;

        while (array_key_exists($name, $this->parameters)) {
            $name = $base . '_' . (++$suffix);
        }

        $this->parameters[$name] = $value;

        return $name;
    }

    /**
     * Adds an 'AND' operator to the condition expressions.
     *
     * @return self Returns the instance for method chaining.
     */
    public function and(): self
    {
        $this->conditions[] = 'AND';
        return $this;
    }

    /**
     * Adds an 'OR' operator to the condition expressions.
     *
     * @return self Returns the instance for method chaining.
     */
    public function or(): self
    {
        $this->conditions[] = 'OR';
        return $this;
    }

    /**
     * Builds and returns the complete SQL expression as a string.
     *
     * @return string The SQL expression.
     */
    public function expression(): string
    {
        return implode(' ', $this->conditions);
    }

    /**
     * Returns the parameters to be bound to the SQL query.
     *
     * @return array An associative array of parameters.
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Adds an equality condition to the expressions.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to compare.
     * @return self Returns the instance for method chaining.
     */
    public function eq(string $key, mixed $value): self
    {
        $this->conditions[] = "$key = :" . $this->bind($key, $value);
        return $this;
    }

    /**
     * Adds a not-equal condition to the expressions.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to compare.
     * @return self Returns the instance for method chaining.
     */
    public function neq(string $key, mixed $value): self
    {
        $this->conditions[] = "$key <> :" . $this->bind($key, $value);
        return $this;
    }

    /**
     * Adds a greater-than condition to the expressions.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to compare.
     * @return self Returns the instance for method chaining.
     */
    public function gt(string $key, mixed $value): self
    {
        $this->conditions[] = "$key > :" . $this->bind($key, $value);
        return $this;
    }

    /**
     * Adds a greater-than-or-equal condition to the expressions.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to compare.
     * @return self Returns the instance for method chaining.
     */
    public function gte(string $key, mixed $value): self
    {
        $this->conditions[] = "$key >= :" . $this->bind($key, $value);
        return $this;
    }

    /**
     * Adds a less-than condition to the expressions.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to compare.
     * @return self Returns the instance for method chaining.
     */
    public function lt(string $key, mixed $value): self
    {
        $this->conditions[] = "$key < :" . $this->bind($key, $value);
        return $this;
    }

    /**
     * Adds a less-than-or-equal condition to the expressions.
     *
     * @param string $key   The column name.
     * @param mixed  $value The value to compare.
     * @return self Returns the instance for method chaining.
     */
    public function lte(string $key, mixed $value): self
    {
        $this->conditions[] = "$key <= :" . $this->bind($key, $value);
        return $this;
    }

    /**
     * Adds an IS NULL condition to the expressions.
     *
     * @param string $key The column name.
     * @return self Returns the instance for method chaining.
     */
    public function null(string $key): self
    {
        $this->conditions[] = "$key IS NULL";
        return $this;
    }

    /**
     * Adds an IS NOT NULL condition to the expressions.
     *
     * @param string $key The column name.
     * @return self Returns the instance for method chaining.
     */
    public function notNull(string $key): self
    {
        $this->conditions[] = "$key IS NOT NULL";
        return $this;
    }

    /**
     * Adds an IN condition to the expressions.
     *
     * @param string $key    The column name.
     * @param array  $values The array of values for the IN clause.
     * @return self Returns the instance for method chaining.
     */
    public function in(string $key, array $values): self
    {
        $placeholders = [];
        foreach ($values as $value) {
            $placeholders[] = ':' . $this->bind($key, $value);
        }
        $inQuery = implode(', ', $placeholders);
        $this->conditions[] = "$key IN ($inQuery)";
        return $this;
    }

    /**
     * Adds a NOT IN condition to the expressions.
     *
     * @param string $key    The column name.
     * @param array  $values The array of values for the NOT IN clause.
     * @return self Returns the instance for method chaining.
     */
    public function notIn(string $key, array $values): self
    {
        $placeholders = [];
        foreach ($values as $value) {
            $placeholders[] = ':' . $this->bind($key, $value);
        }
        $notInQuery = implode(', ', $placeholders);
        $this->conditions[] = "$key NOT IN ($notInQuery)";
        return $this;
    }
}

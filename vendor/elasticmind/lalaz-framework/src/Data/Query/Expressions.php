<?php declare(strict_types=1);

namespace Lalaz\Data\Query;

/**
 * Class Expressions
 *
 * This final class provides a factory method to create instances of the `Expr` class.
 * It serves as a utility to simplify the creation of expression objects used for building SQL queries.
 *
 * @package Lalaz\Data\Query
 */
final class Expressions
{
    /**
     * Creates and returns a new instance of the `Expr` class.
     *
     * @return Expr A new instance of the `Expr` class.
     */
    public static function create(): Expr
    {
        return new Expr();
    }
}

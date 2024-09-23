<?php declare(strict_types=1);

namespace Lalaz\Security;

use Lalaz\Data\Query\Expressions;

/**
 * Trait Authenticable
 *
 * This trait provides methods for user authentication. It abstracts
 * the details of the username and password fields, allowing any class
 * that uses this trait to define those properties. It also includes
 * password verification logic using the PasswordHash trait.
 *
 * @author  Elasticmind <ola@elasticmind.io>
 * @namespace Lalaz\Security
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
trait Authenticable
{
    use PasswordHash;

    /**
     * Gets the name of the property that stores the username.
     *
     * Each class using this trait must implement this method to return
     * the name of the username property.
     *
     * @return string The property name for the username.
     */
    abstract private static function usernamePropertyName(): string;

    /**
     * Gets the name of the property that stores the password.
     *
     * Each class using this trait must implement this method to return
     * the name of the password property.
     *
     * @return string The property name for the password.
     */
    abstract private static function passwordPropertyName(): string;

    /**
     * Authenticates a user based on the provided username and password.
     *
     * This method looks up the user by the username and verifies the password
     * using the PasswordHash trait's verification logic.
     *
     * @param string $username The username to authenticate.
     * @param string $password The password to authenticate.
     *
     * @return mixed Returns the user object if authentication is successful, otherwise false.
     */
    public static function authenticate(string $username, string $password): mixed
    {
        $usernamePropertyName = self::usernamePropertyName();
        $passwordPropertyName = self::passwordPropertyName();

        $filter = Expressions::create()->eq($usernamePropertyName, $username);
        $user = self::findOneByExpression($filter);

        if (!$user) return false;

        $isValidPassword = self::verifyHash($password, $user->{$passwordPropertyName});

        if (!$isValidPassword) return false;

        return $user;
    }
}

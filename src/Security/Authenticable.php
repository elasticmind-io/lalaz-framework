<?php declare(strict_types=1);

namespace Lalaz\Security;

use Lalaz\Http\SessionManager;
use Lalaz\Data\Query\Expressions;

/**
 * Trait Authenticable
 *
 * This trait provides methods for user authentication. It abstracts
 * the details of the username and password fields, allowing any class
 * that uses this trait to define those properties. It also includes
 * password verification logic using the PasswordHash trait.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait Authenticable
{
    use PasswordHash;

    private static string $userSessionKey = '__luser';

    /**
     * Gets the name of the property that stores the username.
     *
     * Each class using this trait must implement this method to return
     * the name of the username property.
     *
     * @return string The property name for the username.
     */
    abstract protected static function usernamePropertyName(): string;

    /**
     * Gets the name of the property that stores the password.
     *
     * Each class using this trait must implement this method to return
     * the name of the password property.
     *
     * @return string The property name for the password.
     */
    abstract protected static function passwordPropertyName(): string;

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

        if (!$user) {
            return false;
        }

        $isValidPassword = self::verifyHash($password, $user->{$passwordPropertyName});

        if (!$isValidPassword) {
            return false;
        }

        SessionManager::set(static::$userSessionKey, $user);

        return $user;
    }

    /**
     * Logs out the user by clearing the session.
     *
     * @return void
     */
    public static function logout(): void
    {
        SessionManager::destroy();
    }

    /**
     * Retrieves the authenticated user from the session.
     *
     * @return mixed|null Returns the user object if authenticated, null otherwise.
     */
    public static function authenticatedUser(): mixed
    {
        return SessionManager::get(static::$userSessionKey);
    }
}

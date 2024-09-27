<?php declare(strict_types=1);

namespace Lalaz\Security;

/**
 * Trait PasswordHash
 *
 * This trait provides methods for generating and verifying password hashes
 * using the Argon2ID algorithm, with an additional static salt for added security.
 *
 * @author  Elasticmind <ola@elasticmind.io>
 * @namespace Lalaz\Security
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
trait PasswordHash
{
    /**
     * A static salt used to enhance password hashing security.
     */
    private const SALT = 'L@laZ1#2F';

    /**
     * Generates a salted hash of the given plain text password using Argon2ID.
     *
     * @param string $plainText The plain text password to hash.
     *
     * @return string The hashed password.
     */
    public static function generateHash(string $plainText): string
    {
        $salted = $plainText . self::SALT;

        $hashed = password_hash(
            $salted,
            PASSWORD_ARGON2ID,
            ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3]
        );

        return $hashed;
    }

    /**
     * Verifies that the provided plain text password matches the given hash.
     *
     * @param string $plainText The plain text password to verify.
     * @param string $hash      The hashed password to compare with.
     *
     * @return bool True if the password matches the hash, false otherwise.
     */
    public static function verifyHash(string $plainText, string $hash): bool
    {
        $salted = $plainText . self::SALT;
        return password_verify($salted, $hash);
    }
}

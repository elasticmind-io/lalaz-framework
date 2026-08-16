<?php declare(strict_types=1);

namespace Lalaz\Security\Concerns;

/**
 * Trait PasswordHash
 *
 * This trait provides methods for generating and verifying password hashes
 * using the Argon2ID algorithm, with an additional static salt for added security.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait PasswordHash
{
    /**
     * A static salt used to enhance password hashing security.
     */
    private const DEFAULT_SALT = 'L@laZ1#2F';

    /**
     * 19456 KiB e o piso do OWASP para Argon2id. Em 2048 cada tentativa custava
     * ~2 ms contra ~145 ms aqui: forca bruta 73x mais barata. O custo fica
     * gravado dentro do hash, entao hash antigo continua validando.
     */
    private const HASH_OPTIONS = ['memory_cost' => 19456, 'time_cost' => 4, 'threads' => 3];

    /**
     * Generates a salted hash of the given plain text password using Argon2ID.
     *
     * @param string $plainText The plain text password to hash.
     *
     * @return string The hashed password.
     */
    public static function generateHash(string $plainText): string
    {
        $hashed = password_hash(
            self::salt($plainText),
            PASSWORD_ARGON2ID,
            self::HASH_OPTIONS
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
        if (password_verify(self::salt($plainText), $hash)) {
            return true;
        }

        // Esquema antigo. O "." liga mais forte que o "??", entao
        // `$plainText . config(...) ?? DEFAULT_SALT` nunca caia no default: sem
        // SECRET_KEY a senha era hasheada SEM pepper. Corrigir isso sozinho
        // trancaria todo mundo pra fora, entao o hash velho continua aceito e o
        // chamador regrava no proximo login — ver needsRehash().
        return password_verify(self::legacySalt($plainText), $hash);
    }

    /**
     * O hash precisa ser regravado?
     *
     * Verdadeiro quando ele so valida pelo esquema antigo de pepper, ou quando
     * foi gerado com parametros de Argon mais fracos que os atuais (o custo
     * antigo era 2048 KiB, hoje e 19456).
     *
     * Usar no login, depois de verifyHash() passar:
     *
     *     if (PasswordHash::verifyHash($senha, $user->password)) {
     *         if (PasswordHash::needsRehash($senha, $user->password)) {
     *             $user->password = PasswordHash::generateHash($senha);
     *             $user->save();
     *         }
     *     }
     *
     * @param string $plainText The password just verified.
     * @param string $hash      The stored hash.
     * @return bool
     */
    public static function needsRehash(string $plainText, string $hash): bool
    {
        if (!password_verify(self::salt($plainText), $hash)) {
            return true;
        }

        return password_needs_rehash($hash, PASSWORD_ARGON2ID, self::HASH_OPTIONS);
    }

    /**
     * Pepper atual. Os parenteses sao o conserto: sem eles o `??` nunca via um
     * null, porque uma concatenacao nunca e null.
     */
    private static function salt(string $plainText): string
    {
        return $plainText . (config('SECRET_KEY') ?? self::DEFAULT_SALT);
    }

    /** O esquema com o bug de precedencia, mantido so para migrar. */
    private static function legacySalt(string $plainText): string
    {
        return $plainText . config('SECRET_KEY');
    }
}

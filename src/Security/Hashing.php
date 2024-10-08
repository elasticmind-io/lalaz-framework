<?php declare(strict_types=1);

namespace Lalaz\Security;

/**
 * Class Hashing
 *
 * This trait provides methods for generating and verifying password hashes
 * using the Argon2ID algorithm, with an additional static salt for added security.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class Hashing
{
    use PasswordHash;
}

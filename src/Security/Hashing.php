<?php declare(strict_types=1);

namespace Lalaz\Security;

/**
 * Class Hashing
 *
 * This trait provides methods for generating and verifying password hashes
 * using the Argon2ID algorithm, with an additional static salt for added security.
 *
 * @author  Elasticmind <ola@elasticmind.io>
 * @namespace Lalaz\Security
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class Hashing
{
    use PasswordHash;
}

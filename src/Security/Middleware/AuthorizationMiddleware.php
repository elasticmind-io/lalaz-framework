<?php declare(strict_types=1);

namespace Lalaz\Security\Middleware;

use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Http\Middleware;
use Lalaz\Security\Authorizable;

/**
 * Class AuthorizationMiddleware
 *
 * Handles role-based access control for routes.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class AuthorizationMiddleware extends Middleware
{
    /**
     * @var array The required roles to access the route.
     */
    protected array $requiredRoles;

    /**
     * AuthorizationMiddleware constructor.
     *
     * @param array $requiredRoles An array of roles required for the route.
     */
    public function __construct(array $requiredRoles = [])
    {
        $this->requiredRoles = $requiredRoles;
    }

    /**
     * Handles the incoming request and verifies if the user has the required role.
     *
     * @param Request $req The incoming HTTP request.
     * @param Response $res The outgoing HTTP response.
     *
     * @return void
     */
    public function handle(Request $req, Response $res): void
    {
        $user = $req->user;

        if (!$this::hasRole($user)) {
            die('Forbidden');
            return;
        }
    }

     /**
     * Checks if the user has any of the required roles.
     *
     * @param Authorizable|null $user The user object implementing the Authorizable interface.
     * @return bool True if the user has any required role, false otherwise.
     */
    private function hasRole($user) : bool
    {
        return $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole($this->requiredRoles);
    }
}

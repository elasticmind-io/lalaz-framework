<?php declare(strict_types=1);

namespace Lalaz\Security\Middleware;

use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Security\Authorizable;
use Lalaz\Http\Middleware;
use Lalaz\Http\SessionManager;

/**
 * Class PermissionMiddleware
 *
 * Middleware to check user permissions before allowing access to the route.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class PermissionMiddleware extends Middleware
{
    /**
     * @var array Permissions required for the route.
     */
    protected array $requiredPermissions;

    /**
     * PermissionMiddleware constructor.
     *
     * @param array $permissions An array of permissions required to access the route.
     */
    public function __construct(array $permissions = [])
    {
        $this->requiredPermissions = $permissions;
    }

    /**
     * Handles the incoming request and checks user permissions.
     *
     * @param Request $req The incoming HTTP request.
     * @param Response $res The outgoing HTTP response.
     *
     * @return void
     */
    public function handle(Request $req, Response $res): void
    {
        $user = SessionManager::get('__luser');

        if (!$this->hasPermission($user)) {
            $res->redirect('/unauthorized');
            return;
        }
    }

    /**
     * Checks if the user has any of the required permissions.
     *
     * @param Authorizable|null $user The user object implementing the Authorizable interface.
     * @return bool True if the user has any required permission, false otherwise.
     */
    private function hasPermission($user): bool
    {
        return $user
            && method_exists($user, 'hasAnyPermission')
            && $user->hasAnyPermission($this->requiredPermissions);
    }
}

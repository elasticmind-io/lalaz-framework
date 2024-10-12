<?php declare(strict_types=1);

namespace Lalaz\Security;

/**
 * Trait Authorizable
 *
 * This trait provides methods for handling authorization, including checking
 * user roles and permissions. Any class using this trait is expected to have
 * properties or methods to define roles and permissions.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
trait Authorizable
{
    /**
     * Cache for roles to avoid repeated calls.
     *
     * @var array|null
     */
    private ?array $cachedRoles = null;

    /**
     * Cache for permissions to avoid repeated calls.
     *
     * @var array|null
     */
    private ?array $cachedPermissions = null;

    /**
     * Get the roles assigned to the user, with caching.
     *
     * @return array The roles assigned to the user.
     */
    public function getRoles(): array
    {
        if ($this->cachedRoles !== null) {
            return $this->cachedRoles;
        }

        $this->cachedRoles = $this->fetchRoles();
        return $this->cachedRoles;
    }

    /**
     * Get the permissions assigned to the user, with caching.
     *
     * @return array The permissions assigned to the user.
     */
    public function getPermissions(): array
    {
        // Use the cached permissions if available
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        // Fetch the permissions from the implementation and cache them
        $this->cachedPermissions = $this->fetchPermissions();
        return $this->cachedPermissions;
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role The role to check for.
     * @return bool True if the user has the role, false otherwise.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * Check if the user has a specific permission.
     *
     * @param string $permission The permission to check for.
     * @return bool True if the user has the permission, false otherwise.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Check if the user has any of the specified permissions.
     *
     * @param array $permissions An array of permissions to check against.
     * @return bool True if the user has any of the permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return (bool) array_intersect($permissions, $this->getPermissions());
    }

    /**
     * Check if the user has all of the specified permissions.
     *
     * @param array $permissions An array of permissions to check against.
     * @return bool True if the user has all of the permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return empty(array_diff($permissions, $this->getPermissions()));
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param array $roles An array of roles to check for.
     * @return bool True if the user has any of the roles, false otherwise.
     */
    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($roles, $this->getRoles()));
    }

    /**
     * Fetch the roles from the implementation.
     *
     * This method is intended to be implemented by the user model and should
     * return an array of roles assigned to the user.
     *
     * @return array The roles assigned to the user.
     */
    abstract protected function fetchRoles(): array;

    /**
     * Fetch the permissions from the implementation.
     *
     * This method is intended to be implemented by the user model and should
     * return an array of permissions assigned to the user.
     *
     * @return array The permissions assigned to the user.
     */
    abstract protected function fetchPermissions(): array;
}

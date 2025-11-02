<?php declare(strict_types=1);

use Lalaz\Security\Authorizable;

describe('Authorizable', function () {
    beforeEach(function () {
        $this->user = new class {
            use Authorizable;

            private array $roles = ['user', 'editor'];
            private array $permissions = ['read', 'write', 'edit'];

            protected function fetchRoles(): array
            {
                return $this->roles;
            }

            protected function fetchPermissions(): array
            {
                return $this->permissions;
            }

            // Helper method to change roles/permissions for testing
            public function setRoles(array $roles): void
            {
                $this->roles = $roles;
                $this->cachedRoles = null;
            }

            public function setPermissions(array $permissions): void
            {
                $this->permissions = $permissions;
                $this->cachedPermissions = null;
            }
        };
    });

    describe('trait structure', function () {
        it('requires fetchRoles() implementation', function () {
            expect(method_exists($this->user, 'fetchRoles'))->toBeTrue();
        });

        it('requires fetchPermissions() implementation', function () {
            expect(method_exists($this->user, 'fetchPermissions'))->toBeTrue();
        });

        it('provides getRoles() method', function () {
            expect(method_exists($this->user, 'getRoles'))->toBeTrue();
        });

        it('provides getPermissions() method', function () {
            expect(method_exists($this->user, 'getPermissions'))->toBeTrue();
        });

        it('provides hasRole() method', function () {
            expect(method_exists($this->user, 'hasRole'))->toBeTrue();
        });

        it('provides hasPermission() method', function () {
            expect(method_exists($this->user, 'hasPermission'))->toBeTrue();
        });

        it('provides hasAnyPermission() method', function () {
            expect(method_exists($this->user, 'hasAnyPermission'))->toBeTrue();
        });

        it('provides hasAllPermissions() method', function () {
            expect(method_exists($this->user, 'hasAllPermissions'))->toBeTrue();
        });

        it('provides hasAnyRole() method', function () {
            expect(method_exists($this->user, 'hasAnyRole'))->toBeTrue();
        });
    });

    describe('getRoles()', function () {
        it('returns user roles', function () {
            $roles = $this->user->getRoles();

            expect($roles)->toBe(['user', 'editor']);
        });

        it('caches roles on first call', function () {
            $roles1 = $this->user->getRoles();
            $roles2 = $this->user->getRoles();

            expect($roles1)->toBe($roles2);
        });

        it('returns cached roles without calling fetchRoles again', function () {
            // Create a mock that tracks fetch calls
            $fetchCallCount = 0;

            $user = new class {
                use Authorizable;
                public int $fetchCallCount = 0;

                protected function fetchRoles(): array
                {
                    $this->fetchCallCount++;
                    return ['user', 'editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            // First call
            $user->getRoles();
            expect($user->fetchCallCount)->toBe(1);

            // Second call should use cache
            $user->getRoles();
            expect($user->fetchCallCount)->toBe(1); // Still 1, not incremented
        });
    });

    describe('getPermissions()', function () {
        it('returns user permissions', function () {
            $permissions = $this->user->getPermissions();

            expect($permissions)->toBe(['read', 'write', 'edit']);
        });

        it('caches permissions on first call', function () {
            $permissions1 = $this->user->getPermissions();
            $permissions2 = $this->user->getPermissions();

            expect($permissions1)->toBe($permissions2);
        });

        it('returns cached permissions without calling fetchPermissions again', function () {
            $user = new class {
                use Authorizable;
                public int $fetchCallCount = 0;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    $this->fetchCallCount++;
                    return ['read', 'write', 'edit'];
                }
            };

            // First call
            $user->getPermissions();
            expect($user->fetchCallCount)->toBe(1);

            // Second call should use cache
            $user->getPermissions();
            expect($user->fetchCallCount)->toBe(1); // Still 1, not incremented
        });
    });

    describe('hasRole()', function () {
        it('returns true when user has the role', function () {
            expect($this->user->hasRole('user'))->toBeTrue();
            expect($this->user->hasRole('editor'))->toBeTrue();
        });

        it('returns false when user does not have the role', function () {
            expect($this->user->hasRole('admin'))->toBeFalse();
            expect($this->user->hasRole('moderator'))->toBeFalse();
        });

        it('is case sensitive', function () {
            expect($this->user->hasRole('User'))->toBeFalse();
            expect($this->user->hasRole('EDITOR'))->toBeFalse();
        });

        it('handles empty role name', function () {
            expect($this->user->hasRole(''))->toBeFalse();
        });
    });

    describe('hasPermission()', function () {
        it('returns true when user has the permission', function () {
            expect($this->user->hasPermission('read'))->toBeTrue();
            expect($this->user->hasPermission('write'))->toBeTrue();
            expect($this->user->hasPermission('edit'))->toBeTrue();
        });

        it('returns false when user does not have the permission', function () {
            expect($this->user->hasPermission('delete'))->toBeFalse();
            expect($this->user->hasPermission('admin'))->toBeFalse();
        });

        it('is case sensitive', function () {
            expect($this->user->hasPermission('Read'))->toBeFalse();
            expect($this->user->hasPermission('WRITE'))->toBeFalse();
        });

        it('handles empty permission name', function () {
            expect($this->user->hasPermission(''))->toBeFalse();
        });
    });

    describe('hasAnyPermission()', function () {
        it('returns true when user has at least one permission', function () {
            expect($this->user->hasAnyPermission(['read', 'delete']))->toBeTrue();
            expect($this->user->hasAnyPermission(['admin', 'write']))->toBeTrue();
        });

        it('returns false when user has none of the permissions', function () {
            expect($this->user->hasAnyPermission(['delete', 'admin']))->toBeFalse();
        });

        it('returns true when user has all permissions', function () {
            expect($this->user->hasAnyPermission(['read', 'write', 'edit']))->toBeTrue();
        });

        it('returns false for empty array', function () {
            expect($this->user->hasAnyPermission([]))->toBeFalse();
        });

        it('handles single permission', function () {
            expect($this->user->hasAnyPermission(['read']))->toBeTrue();
            expect($this->user->hasAnyPermission(['delete']))->toBeFalse();
        });
    });

    describe('hasAllPermissions()', function () {
        it('returns true when user has all specified permissions', function () {
            expect($this->user->hasAllPermissions(['read', 'write']))->toBeTrue();
            expect($this->user->hasAllPermissions(['read', 'write', 'edit']))->toBeTrue();
        });

        it('returns false when user is missing at least one permission', function () {
            expect($this->user->hasAllPermissions(['read', 'delete']))->toBeFalse();
            expect($this->user->hasAllPermissions(['read', 'write', 'admin']))->toBeFalse();
        });

        it('returns true for empty array', function () {
            expect($this->user->hasAllPermissions([]))->toBeTrue();
        });

        it('handles single permission', function () {
            expect($this->user->hasAllPermissions(['read']))->toBeTrue();
            expect($this->user->hasAllPermissions(['delete']))->toBeFalse();
        });

        it('returns false when user has none of the permissions', function () {
            expect($this->user->hasAllPermissions(['delete', 'admin']))->toBeFalse();
        });
    });

    describe('hasAnyRole()', function () {
        it('returns true when user has at least one role', function () {
            expect($this->user->hasAnyRole(['user', 'admin']))->toBeTrue();
            expect($this->user->hasAnyRole(['moderator', 'editor']))->toBeTrue();
        });

        it('returns false when user has none of the roles', function () {
            expect($this->user->hasAnyRole(['admin', 'moderator']))->toBeFalse();
        });

        it('returns true when user has all roles', function () {
            expect($this->user->hasAnyRole(['user', 'editor']))->toBeTrue();
        });

        it('returns false for empty array', function () {
            expect($this->user->hasAnyRole([]))->toBeFalse();
        });

        it('handles single role', function () {
            expect($this->user->hasAnyRole(['user']))->toBeTrue();
            expect($this->user->hasAnyRole(['admin']))->toBeFalse();
        });
    });

    describe('caching behavior', function () {
        it('uses cached roles in hasRole checks', function () {
            $user = new class {
                use Authorizable;
                public int $fetchCallCount = 0;

                protected function fetchRoles(): array
                {
                    $this->fetchCallCount++;
                    return ['user', 'editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            // Multiple hasRole calls should only fetch once
            $user->hasRole('user');
            expect($user->fetchCallCount)->toBe(1);

            $user->hasRole('editor');
            expect($user->fetchCallCount)->toBe(1);

            $user->hasRole('admin');
            expect($user->fetchCallCount)->toBe(1);
        });

        it('uses cached permissions in hasPermission checks', function () {
            $user = new class {
                use Authorizable;
                public int $fetchCallCount = 0;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    $this->fetchCallCount++;
                    return ['read', 'write'];
                }
            };

            // Multiple hasPermission calls should only fetch once
            $user->hasPermission('read');
            expect($user->fetchCallCount)->toBe(1);

            $user->hasPermission('write');
            expect($user->fetchCallCount)->toBe(1);

            $user->hasPermission('delete');
            expect($user->fetchCallCount)->toBe(1);
        });
    });

    describe('edge cases', function () {
        it('handles user with no roles', function () {
            $emptyUser = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            expect($emptyUser->getRoles())->toBe([]);
            expect($emptyUser->hasRole('user'))->toBeFalse();
            expect($emptyUser->hasAnyRole(['user', 'admin']))->toBeFalse();
        });

        it('handles user with no permissions', function () {
            $emptyUser = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            expect($emptyUser->getPermissions())->toBe([]);
            expect($emptyUser->hasPermission('read'))->toBeFalse();
            expect($emptyUser->hasAnyPermission(['read', 'write']))->toBeFalse();
            expect($emptyUser->hasAllPermissions([]))->toBeTrue();
        });

        it('handles duplicate roles', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return ['user', 'user', 'editor'];
                }

                protected function fetchPermissions(): array
                {
                    return [];
                }
            };

            expect($user->getRoles())->toBe(['user', 'user', 'editor']);
            expect($user->hasRole('user'))->toBeTrue();
        });

        it('handles duplicate permissions', function () {
            $user = new class {
                use Authorizable;

                protected function fetchRoles(): array
                {
                    return [];
                }

                protected function fetchPermissions(): array
                {
                    return ['read', 'read', 'write'];
                }
            };

            expect($user->getPermissions())->toBe(['read', 'read', 'write']);
            expect($user->hasPermission('read'))->toBeTrue();
        });
    });
});

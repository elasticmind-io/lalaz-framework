<?php declare(strict_types=1);

namespace Lalaz\Security\Middleware;

use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Http\Middleware;
use Lalaz\Http\SessionManager;

/**
 * Class AuthenticationMiddleware
 *
 * Handles the authentication logic, ensuring that a user is logged in.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class AuthenticationMiddleware extends Middleware
{
    private string $loginUrl = '';

    public function __construct(string $loginUrl = '')
    {
        $this->loginUrl = $loginUrl;
    }

    /**
     * Handle the incoming request to verify user authentication.
     *
     * @param Request $req The incoming HTTP request.
     * @param Response $res The outgoing HTTP response.
     *
     * @return void
     */
    public function handle(Request $req, Response $res): void
    {
        $user = SessionManager::get('__luser');

        if (!$user) {
            if (strlen($this->loginUrl) > 0) {
                $res->redirect($this->loginUrl);
                return;
            }

            die('Forbidden');
            return;
        }

        $req->user = $user;
    }
}

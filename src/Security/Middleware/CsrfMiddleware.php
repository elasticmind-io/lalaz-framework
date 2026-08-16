<?php declare(strict_types=1);

namespace Lalaz\Security\Middleware;

use Lalaz\Http\Request;
use Lalaz\Http\Response;
use Lalaz\Http\Middleware;

/**
 * Class CsrfMiddleware
 *
 * Aplica a verificacao de token CSRF a um grupo de rotas.
 *
 * A verificacao ja existia no Request, mas era opt-in: cabia a cada controller
 * lembrar de chama-la. E assim que se chega a um endpoint protegido de nove —
 * foi o que aconteceu no codeinit.dev, onde so o login validava enquanto criar,
 * editar e excluir post passavam direto. Aplicada no grupo, a regra deixa de
 * depender de alguem lembrar.
 *
 * A validacao em si continua sendo a do Request: cookie HttpOnly, comparacao
 * com hash_equals e rotacao do token apos operacao que muda estado. Este
 * middleware so decide ONDE ela roda. Requisicao sem token valido levanta
 * HttpException::csrfMismatch, que responde 419.
 *
 * Uso:
 *
 *     Route::group('/lpanel', function ($router) { ... })
 *         ->middleware(CsrfMiddleware::class);
 *
 * Endpoint que nao tem como carregar token — webhook de terceiro, por exemplo —
 * fica de fora pelo construtor:
 *
 *     ->middleware(new CsrfMiddleware(['/api/webhooks']));
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class CsrfMiddleware extends Middleware
{
    /** @var string[] Prefixos de caminho isentos da verificacao. */
    private array $isentos;

    /**
     * @param string[] $isentos Prefixos de caminho que nao devem ser validados.
     */
    public function __construct(array $isentos = [])
    {
        $this->isentos = $isentos;
    }

    /**
     * @param Request $req The incoming HTTP request.
     * @param Response $res The outgoing HTTP response.
     * @return void
     */
    public function handle(Request $req, Response $res): void
    {
        if ($this->isento()) {
            return;
        }

        // O Request ja ignora metodo que nao muda estado, entao nao ha nada a
        // filtrar aqui: um GET atravessa sem custo.
        $req->validateCsrfToken();
    }

    /**
     * O caminho pedido esta na lista de isentos?
     */
    private function isento(): bool
    {
        $caminho = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';

        foreach ($this->isentos as $prefixo) {
            if (str_starts_with($caminho, $prefixo)) {
                return true;
            }
        }

        return false;
    }
}

<?php declare(strict_types=1);

use Lalaz\Security\Concerns\PasswordHash;

/**
 * A migracao do pepper nao pode trancar ninguem pra fora.
 *
 * O bug: `$plainText . config('SECRET_KEY') ?? self::DEFAULT_SALT`. Em PHP o "."
 * liga mais forte que o "??", entao o lado esquerdo — uma concatenacao — nunca e
 * null e o DEFAULT_SALT era codigo morto. Sem SECRET_KEY a senha ia sem pepper.
 *
 * Corrigir a precedencia muda a entrada do hash. Feito seco, todo hash gravado
 * para de validar. Por isso verifyHash() aceita os dois esquemas e needsRehash()
 * diz quando regravar.
 */

class ContaFalsa
{
    use PasswordHash;
}

/** Reproduz o esquema antigo, com o bug de precedencia. */
function hashLegado(string $senha): string
{
    $salted = $senha . config('SECRET_KEY') ?? 'L@laZ1#2F';

    return password_hash(
        $salted,
        PASSWORD_ARGON2ID,
        ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3]
    );
}

it('valida senha gravada pelo esquema antigo', function () {
    $hash = hashLegado('segredo123');

    expect(ContaFalsa::verifyHash('segredo123', $hash))->toBeTrue();
});

it('marca o hash antigo para regravacao', function () {
    $hash = hashLegado('segredo123');

    expect(ContaFalsa::needsRehash('segredo123', $hash))->toBeTrue();
});

it('para de pedir regravacao depois de regravar', function () {
    $antigo = hashLegado('segredo123');
    expect(ContaFalsa::needsRehash('segredo123', $antigo))->toBeTrue();

    $novo = ContaFalsa::generateHash('segredo123');

    expect(ContaFalsa::verifyHash('segredo123', $novo))->toBeTrue();
    expect(ContaFalsa::needsRehash('segredo123', $novo))->toBeFalse();
});

it('continua recusando senha errada nos dois esquemas', function () {
    expect(ContaFalsa::verifyHash('errada', hashLegado('segredo123')))->toBeFalse();
    expect(ContaFalsa::verifyHash('errada', ContaFalsa::generateHash('segredo123')))->toBeFalse();
});

it('pede regravacao de hash gerado com Argon mais fraco', function () {
    // mesmo pepper atual, mas com o custo antigo
    // setAccessible() e no-op deprecado desde o PHP 8.1
    $comPepperCerto = (new ReflectionMethod(ContaFalsa::class, 'salt'))->invoke(null, 'segredo123');

    $fraco = password_hash(
        $comPepperCerto,
        PASSWORD_ARGON2ID,
        ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3]
    );

    expect(ContaFalsa::verifyHash('segredo123', $fraco))->toBeTrue();
    expect(ContaFalsa::needsRehash('segredo123', $fraco))->toBeTrue();
});

it('grava com o custo do OWASP', function () {
    $info = password_get_info(ContaFalsa::generateHash('segredo123'));

    expect($info['options']['memory_cost'])->toBeGreaterThanOrEqual(19456);
});

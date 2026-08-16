<?php declare(strict_types=1);

use Lalaz\Data\Query\Expressions;

/**
 * O marcador de cada condicao precisa ser unico.
 *
 * O comportamento anterior indexava o parametro pelo NOME DA COLUNA, entao duas
 * condicoes sobre a mesma coluna geravam dois marcadores iguais e um valor so —
 * a segunda sobrescrevia a primeira em silencio. Um intervalo de datas virava
 * "created_at > X AND created_at < X", que nunca casa nada.
 */

it('mantem o nome simples quando a coluna aparece uma vez', function () {
    $expr = Expressions::create()
        ->eq('slug', 'abc')
        ->and()
        ->eq('draft', 0);

    expect($expr->expression())->toBe('slug = :slug AND draft = :draft');
    expect($expr->parameters())->toBe(['slug' => 'abc', 'draft' => 0]);
});

it('nao deixa duas condicoes na mesma coluna colidirem', function () {
    $expr = Expressions::create()
        ->gt('created_at', '2026-08-01')
        ->and()
        ->lt('created_at', '2026-08-15');

    $parametros = $expr->parameters();

    // as duas pontas do intervalo tem que sobreviver
    expect($parametros)->toHaveCount(2);
    expect(array_values($parametros))->toBe(['2026-08-01', '2026-08-15']);

    // e cada uma tem que ter marcador proprio na expressao
    preg_match_all('/:(\w+)/', $expr->expression(), $m);
    expect($m[1])->toHaveCount(2);
    expect(array_unique($m[1]))->toHaveCount(2);
});

it('preserva os dois lados de um OR na mesma coluna', function () {
    $expr = Expressions::create()
        ->eq('id', 1)
        ->or()
        ->eq('id', 2);

    expect(array_values($expr->parameters()))->toBe([1, 2]);
});

it('todo marcador da expressao existe nos parametros', function () {
    $expr = Expressions::create()
        ->eq('status', 'ativo')
        ->and()->gte('views', 10)
        ->and()->lte('views', 100)
        ->and()->neq('status', 'rascunho');

    preg_match_all('/:(\w+)/', $expr->expression(), $m);

    foreach ($m[1] as $marcador) {
        expect($expr->parameters())->toHaveKey($marcador);
    }

    expect($expr->parameters())->toHaveCount(count($m[1]));
});

it('gera marcador valido para coluna qualificada', function () {
    // ":posts.slug" nao e um marcador aceito pelo PDO — o ponto nao pode passar
    $expr = Expressions::create()->eq('posts.slug', 'abc');

    preg_match_all('/:(\w+)/', $expr->expression(), $m);

    expect($m[1])->toHaveCount(1);
    expect($expr->parameters())->toHaveKey($m[1][0]);
    expect(array_values($expr->parameters()))->toBe(['abc']);
});

it('continua nomeando cada valor de um IN separadamente', function () {
    $expr = Expressions::create()->in('id', [1, 2, 3]);

    preg_match_all('/:(\w+)/', $expr->expression(), $m);

    expect($m[1])->toHaveCount(3);
    expect(array_unique($m[1]))->toHaveCount(3);
    expect(array_values($expr->parameters()))->toBe([1, 2, 3]);
});

it('nao colide quando um IN e um comparador usam a mesma coluna', function () {
    $expr = Expressions::create()
        ->in('id', [1, 2])
        ->and()
        ->neq('id', 9);

    preg_match_all('/:(\w+)/', $expr->expression(), $m);

    expect(array_unique($m[1]))->toHaveCount(3);
    expect($expr->parameters())->toHaveCount(3);
    expect(array_values($expr->parameters()))->toBe([1, 2, 9]);
});

it('IS NULL nao cria parametro', function () {
    $expr = Expressions::create()->null('deleted_at');

    expect($expr->expression())->toBe('deleted_at IS NULL');
    expect($expr->parameters())->toBe([]);
});

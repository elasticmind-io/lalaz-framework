<?php declare(strict_types=1);

use Lalaz\View\View;

/**
 * O caminho de erro da View.
 *
 * Historico: renderNotFound passava 404 no terceiro parametro de render(), que e
 * `bool $resetContext` — com strict_types isso lanca TypeError DENTRO do tratador
 * de erro, e toda URL sem rota respondia 500 em vez de 404. Depois renderJson foi
 * apagado e continuou sendo chamado, e em seguida voltou como metodo de INSTANCIA
 * ainda sendo chamado com static:: — que tambem e fatal.
 *
 * Nenhum teste cobria essas funcoes, e foi por isso que os tres passaram.
 */

it('os metodos declarados na propria View sao todos estaticos', function () {
    $classe = new ReflectionClass(View::class);

    // so o que a View declara: metodo vindo de trait (FlashMessage) tem outra
    // regra e nao e chamado com static:: aqui
    $deInstancia = array_values(array_map(
        fn(ReflectionMethod $m) => $m->getName(),
        array_filter(
            $classe->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $m) => !$m->isStatic()
                && !$m->isConstructor()
                && $m->getDeclaringClass()->getName() === View::class
                && $m->getFileName() === $classe->getFileName()
        )
    ));

    expect($deInstancia)->toBe([]);
});

it('toda chamada static:: do arquivo aponta para um metodo estatico', function () {
    $fonte = file_get_contents((new ReflectionClass(View::class))->getFileName());

    preg_match_all('/static::(\w+)\s*\(/', $fonte, $m);

    expect($m[1])->not->toBeEmpty();

    foreach (array_unique($m[1]) as $metodo) {
        expect(method_exists(View::class, $metodo))
            ->toBeTrue("static::{$metodo}() e chamado mas o metodo nao existe");

        expect((new ReflectionMethod(View::class, $metodo))->isStatic())
            ->toBeTrue("static::{$metodo}() e chamado mas o metodo nao e estatico");
    }
});

it('renderJson responde chamada estatica e escreve o JSON', function () {
    ob_start();
    View::renderJson(['status' => 'error', 'message' => 'falhou'], 500);
    $saida = ob_get_clean();

    expect(json_decode($saida, true))
        ->toBe(['status' => 'error', 'message' => 'falhou']);
});

it('nao passa status HTTP onde render() espera um booleano', function () {
    $fonte = file_get_contents((new ReflectionClass(View::class))->getFileName());

    // render(string $view, array $data = [], bool $resetContext = true)
    preg_match_all('/static::render\([^;]*?\);/s', $fonte, $m);

    foreach ($m[0] as $chamada) {
        expect($chamada)->not->toMatch('/,\s*\d{3}\s*\)/');
    }
});

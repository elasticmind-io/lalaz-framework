<?php declare(strict_types=1);

use Lalaz\Core\Config;

if (!function_exists('config'))
{
    function config($key)
    {
        return Config::get($key);
    }
}

if (!function_exists('env'))
{
    function env($key, $defaultValue = null)
    {
        // REGISTRA o default quando a chave nao existe, em vez de so ler.
        //
        // Os arquivos Config/*.php da aplicacao DECLARAM a configuracao
        // chamando env('CHAVE', padrao), e o Loader apenas da require neles —
        // o array retornado e descartado. Entao esta chamada e o unico ponto
        // em que aquele valor entra no Config.
        //
        // Como Config::get puro, todo Config/*.php virava no-op:
        // TEMPLATE_PROVIDER, SECRET_KEY, ENV, APP_DEBUG e mais uma duzia de
        // chaves passavam a devolver null, e a aplicacao caia na primeira
        // renderizacao com "TEMPLATE_PROVIDER was not provided".
        if (Config::get($key) === null) {
            return Config::set($key, $defaultValue);
        }

        return Config::get($key);
    }
}

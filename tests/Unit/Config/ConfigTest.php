<?php

use Lalaz\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV = [];
        $reflection = new \ReflectionClass(Config::class);
        $envProperty = $reflection->getProperty('env');
        $envProperty->setAccessible(true);
        $envProperty->setValue(null);
    }

    /**
     * Test that the load method loads environment variables from a file.
     */
    public function testShouldLoadEnvironmentVariablesFromFile()
    {
        // Arrange: Cria um arquivo temporário com variáveis de ambiente
        $envFile = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($envFile, "DB_HOST=localhost\nDB_USER=root\nDB_PASSWORD=secret\n");

        // Act: Chama o método load para carregar as variáveis
        Config::load($envFile);

        // Assert: Verifica se as variáveis foram carregadas corretamente no $_ENV
        $this->assertEquals('localhost', $_ENV['DB_HOST']);
        $this->assertEquals('root', $_ENV['DB_USER']);
        $this->assertEquals('secret', $_ENV['DB_PASSWORD']);

        // Remove o arquivo temporário
        unlink($envFile);
    }

    /**
     * Test that load method does not reload environment variables if already loaded.
     */
    public function testShouldNotReloadEnvironmentVariablesIfAlreadyLoaded()
    {
        // Arrange: Define variáveis diretamente no $_ENV e na propriedade estática
        $_ENV['EXISTING_VAR'] = 'value1';
        Config::load('/fake/path/to/env');  // Primeira carga

        // Act: Tenta recarregar (mas como já foi carregado, não deve modificar)
        Config::load('/another/path/to/env');

        // Assert: Verifica se a variável no $_ENV não foi sobrescrita
        $this->assertEquals('value1', $_ENV['EXISTING_VAR']);
    }

    /**
     * Test that get method returns the value of an existing environment variable.
     */
    public function testShouldReturnEnvironmentVariableValueWhenKeyExists()
    {
        // Arrange: Carrega uma variável de ambiente diretamente
        $_ENV['APP_ENV'] = 'development';
        Config::load('/fake/path/to/env');  // Simula o carregamento

        // Act: Recupera o valor da variável de ambiente
        $value = Config::get('APP_ENV');

        // Assert: Verifica se o valor retornado está correto
        $this->assertEquals('development', $value);
    }

    /**
     * Test that get method returns null when environment variable key does not exist.
     */
    public function testShouldReturnNullWhenEnvironmentVariableKeyDoesNotExist()
    {
        // Arrange: Simula o carregamento de variáveis de ambiente
        Config::load('/fake/path/to/env');

        // Act: Tenta obter uma variável que não existe
        $value = Config::get('NON_EXISTENT_VAR');

        // Assert: Verifica se o valor retornado é null
        $this->assertNull($value);
    }
}

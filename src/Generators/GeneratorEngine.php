<?php

namespace Lalaz\Generators;

use Lalaz\IO\Directory;

/**
 * Class GeneratorEngine
 *
 * This class is responsible for generating various application components (controllers, models, views, etc.)
 * based on templates. It dynamically creates files and directories using a given template and output file path.
 *
 * @author  Elasticmind
 * @namespace Lalaz\Generators
 * @package  elasticmind\lalaz-framework
 * @link     https://elasticmind.io
 */
class GeneratorEngine
{
    /** @var string $templatePath The path to the template file. */
    protected $templatePath;

    /** @var string $outputFilePath The path where the generated file will be saved. */
    protected $outputFilePath;

    /** @var array $variables Variables to be replaced in the template file. */
    protected $variables = [];

    /**
     * Constructor for the GeneratorEngine class.
     *
     * @param string $templateName The name of the template file to use.
     * @param string $outputFilePath The output file path for the generated file.
     */
    public function __construct($templateName, $outputFilePath)
    {
        $this->templatePath = __DIR__ . '/templates/' . $templateName;
        $this->outputFilePath = $outputFilePath;
    }

    /**
     * Renders the template file by replacing the placeholders with the provided variables.
     *
     * @return string The rendered template content.
     * @throws \Exception If the template file is not found.
     */
    private function render(): string
    {
        if (!file_exists($this->templatePath)) {
            throw new \Exception("Template file not found: {$this->templatePath}");
        }

        $templateContent = file_get_contents($this->templatePath);

        foreach ($this->variables as $key => $value) {
            $templateContent = str_replace('{{' . $key . '}}', $value, $templateContent);
        }

        return $templateContent;
    }

    /**
     * Sets the variables to be replaced in the template.
     *
     * @param array $variables An associative array of variables and their values.
     * @return void
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    /**
     * Generates the file by rendering the template and saving it to the output file path.
     *
     * @return void
     */
    public function generate(): void
    {
        Directory::ensureDirectoryExists($this->outputFilePath);
        $contents = $this->render();
        file_put_contents($this->outputFilePath, $contents);
    }

    /**
     * Parses the name and namespace from a given path and returns the class name, namespace, and directory.
     *
     * @param string $name The path or name provided for parsing.
     * @return array An associative array containing 'className', 'namespace', and 'directory'.
     */
    public static function parseNameAndNamespace($name)
    {
        $pathParts = explode('/', $name);

        $className = array_pop($pathParts);
        $className = ucwords($className);

        $namespace = implode('\\', array_map('ucwords', $pathParts));

        return [
            'className' => $className,
            'namespace' => $namespace,
            'directory' => $namespace ? str_replace('\\', '/', $namespace) . '/' : ''
        ];
    }

    /**
     * Generates a controller file based on the given name.
     *
     * @param string $name The name of the controller to generate.
     * @return void
     */
    public static function controller($name): void
    {
        $parsed = self::parseNameAndNamespace($name);

        $outputFilePath = './src/App/Controllers/'
            . $parsed['directory'] . $parsed['className']
            . 'Controller.php';

        $engine = new GeneratorEngine(
            'controller.tpl',
            $outputFilePath
        );

        $engine->setVariables([
            'name' => $parsed['className'],
            'namespace' => $parsed['namespace']
                ? 'App\\Controllers\\' . $parsed['namespace']
                : 'App\\Controllers'
        ]);

        $engine->generate();
    }

    /**
     * Generates a middleware file based on the given name.
     *
     * @param string $name The name of the middleware to generate.
     * @return void
     */
    public static function middleware($name): void
    {
        $parsed = self::parseNameAndNamespace($name);

        $outputFilePath = './src/App/Middlewares/'
            . $parsed['directory'] . $parsed['className']
            . 'Middleware.php';

        $engine = new GeneratorEngine(
            'middleware.tpl',
            $outputFilePath
        );

        $engine->setVariables([
            'name' => $parsed['className'],
            'namespace' => $parsed['namespace']
                ? 'App\\Middlewares\\' . $parsed['namespace']
                : 'App\\Middlewares'
        ]);

        $engine->generate();
    }

    /**
     * Generates a migration file based on the given name.
     *
     * @param string $name The name of the migration to generate.
     * @return void
     */
    public static function migration($name): void
    {
        $className = ucwords($name);

        $timestamp = date('Ymd_His');
        $filename = "./src/Db/Migrations/{$timestamp}_{$className}.php";

        $engine = new GeneratorEngine(
            'migration.tpl',
            $filename
        );

        $engine->setVariables([
            'name' => $className
        ]);

        $engine->generate();
    }

    /**
     * Generates a model file based on the given name.
     *
     * @param string $name The name of the model to generate.
     * @return void
     */
    public static function model($name): void
    {
        $parsed = self::parseNameAndNamespace($name);

        $outputFilePath = './src/App/Models/'
            . $parsed['directory'] . $parsed['className']
            . 'Model.php';

        $engine = new GeneratorEngine(
            'model.tpl',
            $outputFilePath
        );

        $engine->setVariables([
            'name' => $parsed['className'],
            'namespace' => $parsed['namespace']
                ? 'App\\Models\\' . $parsed['namespace']
                : 'App\\Models'
        ]);

        $engine->generate();
    }

    /**
     * Generates an entity file based on the given name.
     *
     * @param string $name The name of the entity to generate.
     * @return void
     */
    public static function entity($name): void
    {
        $parsed = self::parseNameAndNamespace($name);

        $outputFilePath = './src/App/Models/Entities/'
            . $parsed['directory'] . $parsed['className']
            . '.php';

        $engine = new GeneratorEngine(
            'entity.tpl',
            $outputFilePath
        );

        $engine->setVariables([
            'name' => $parsed['className'],
            'tableName' => strtolower($parsed['className']),
            'namespace' => $parsed['namespace']
                ? 'App\\Models\\Entities\\' . $parsed['namespace']
                : 'App\\Models\\Entities'
        ]);

        $engine->generate();
    }

    /**
     * Generates a presenter file based on the given name.
     *
     * @param string $name The name of the presenter to generate.
     * @return void
     */
    public static function presenter($name): void
    {
        $parsed = self::parseNameAndNamespace($name);

        $outputFilePath = './src/App/Models/Presenters/'
            . $parsed['directory'] . $parsed['className']
            . 'Presenter.php';

        $engine = new GeneratorEngine(
            'presenter.tpl',
            $outputFilePath
        );

        $engine->setVariables([
            'name' => $parsed['className'],
            'namespace' => $parsed['namespace']
                ? 'App\\Models\\Presenters\\' . $parsed['namespace']
                : 'App\\Models\\Presenters'
        ]);

        $engine->generate();
    }

    /**
     * Generates a view file based on the given name.
     *
     * @param string $name The name of the view to generate.
     * @return void
     */
    public static function view($name): void
    {
        $className = strtolower($name);

        $engine = new GeneratorEngine(
            'view.tpl',
            "./src/App/Views/${className}.twig"
        );

        $engine->setVariables([
            'name' => $className
        ]);

        $engine->generate();
    }
}

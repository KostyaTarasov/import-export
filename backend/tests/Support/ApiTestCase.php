<?php

declare(strict_types=1);

namespace App\Tests\Support;

use DI\Container;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\PathFinder;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class ApiTestCase extends DatabaseTestCase
{
    private static ?ResponseValidator $specValidator = null;

    protected function replaceService(string $id, mixed $value): void
    {
        if (!$this->container instanceof Container) {
            throw new LogicException('Подмена сервисов поддерживается только для PHP-DI');
        }

        $this->container->set($id, $value);
    }

    /**
     * @return App<ContainerInterface>
     */
    protected function app(): App
    {
        return $this->container->get(App::class);
    }

    protected function request(string $method, string $uri): ResponseInterface
    {
        $request = new ServerRequestFactory()->createServerRequest($method, $uri);
        $response = $this->app()->handle($request);

        $this->assertMatchesSpec($method, $request->getUri()->getPath(), $response);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function assertMatchesSpec(string $method, string $path, ResponseInterface $response): void
    {
        self::$specValidator ??= new ValidatorBuilder()
            ->fromYamlFile(dirname(__DIR__, 2) . '/openapi.yaml')
            ->getResponseValidator();

        $operations = new PathFinder(self::$specValidator->getSchema(), $path, strtolower($method))->search();

        if ($operations === []) {
            return;
        }

        try {
            self::$specValidator->validate($operations[0], $response);
        } catch (ValidationFailed $e) {
            self::fail(sprintf('Ответ %s %s не соответствует openapi.yaml: %s %s', $method, $path, $e->getMessage(), $e->getPrevious()?->getMessage()));
        }
    }
}

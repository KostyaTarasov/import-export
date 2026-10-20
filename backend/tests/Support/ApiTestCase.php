<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\User;
use App\Service\Auth\JwtEncoder;
use DI\Container;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\PathFinder;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

abstract class ApiTestCase extends DatabaseTestCase
{
    private static ?ResponseValidator $specValidator = null;

    private ?string $token = null;

    /**
     * @var list<string>
     */
    private array $uploads = [];

    protected function tearDown(): void
    {
        parent::tearDown();

        array_map(unlink(...), array_filter($this->uploads, is_file(...)));
    }

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

    protected function authenticate(?User $user = null): User
    {
        $user ??= $this->createUser();
        $this->token = $this->container->get(JwtEncoder::class)->issue($user)->token;

        return $user;
    }

    protected function useToken(?string $token): void
    {
        $this->token = $token;
    }

    protected function upload(string $uri, string $source, string $clientName, ?int $size = null): ResponseInterface
    {
        $copy = $this->uploads[] = (string) tempnam(sys_get_temp_dir(), 'upload');
        copy($source, $copy);

        $request = new ServerRequestFactory()
            ->createServerRequest('POST', $uri)
            ->withUploadedFiles(['file' => new UploadedFile($copy, $clientName, 'application/octet-stream', $size ?? (int) filesize($copy))]);

        return $this->send($request);
    }

    /**
     * @param array<string, mixed>|null $json
     */
    protected function request(string $method, string $uri, ?array $json = null): ResponseInterface
    {
        $request = new ServerRequestFactory()->createServerRequest($method, $uri);

        if ($json !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new StreamFactory()->createStream(json_encode($json, JSON_THROW_ON_ERROR)));
        }

        return $this->send($request);
    }

    private function send(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->token);
        }

        $response = $this->app()->handle($request);

        $this->assertMatchesSpec($request->getMethod(), $request->getUri()->getPath(), $response);

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

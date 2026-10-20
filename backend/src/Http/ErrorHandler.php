<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\ApiException;
use App\Exception\TooManyRequestsException;
use App\Exception\UnauthorizedException;
use App\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

final readonly class ErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private JsonResponder $responder,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        [$status, $code, $message] = match (true) {
            $exception instanceof ApiException => [$exception->statusCode(), $exception->errorCode(), $exception->getMessage()],
            $exception instanceof HttpNotFoundException => [404, 'not_found', 'Ресурс не найден'],
            $exception instanceof HttpMethodNotAllowedException => [405, 'method_not_allowed', 'Метод не поддерживается'],
            $exception instanceof HttpBadRequestException => [400, 'bad_request', 'Некорректный запрос'],
            default => [500, 'internal_error', 'Внутренняя ошибка сервера'],
        };

        $error = ['code' => $code, 'message' => $message];

        if ($exception instanceof ValidationException) {
            $error['fields'] = $exception->errors;
        }

        if ($status >= 500 && !$exception instanceof ApiException) {
            $this->logger->error('Необработанное исключение', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'exception' => $exception,
            ]);

            if ($displayErrorDetails) {
                $error['debug'] = [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile() . ':' . $exception->getLine(),
                ];
            }
        }

        $response = $this->responder->json(['error' => $error], $status);

        if ($exception instanceof HttpMethodNotAllowedException) {
            $response = $response->withHeader('Allow', implode(', ', $exception->getAllowedMethods()));
        }

        if ($exception instanceof UnauthorizedException) {
            $response = $response->withHeader('WWW-Authenticate', 'Bearer');
        }

        if ($exception instanceof TooManyRequestsException) {
            $response = $response->withHeader('Retry-After', (string) $exception->retryAfter);
        }

        return $response;
    }
}

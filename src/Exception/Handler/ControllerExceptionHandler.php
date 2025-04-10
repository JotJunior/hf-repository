<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for
 * manage controllers, entities and repositories.
 *
 * @author   Joao Zanon <jot@jot.com.br>
 * @link     https://github.com/JotJunior/hf-repository
 * @license  MIT
 */

namespace Jot\HfRepository\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\RateLimit\Exception\RateLimitException;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Jot\HfRepository\Exception\RepositoryUpdateException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function Hyperf\Translation\__;

class ControllerExceptionHandler extends ExceptionHandler
{
    private const EXCEPTION_HANDLERS = [
        RateLimitException::class => [
            'status' => 429,
            'handler' => 'handleRateLimitException',
        ],
        EntityValidationWithErrorsException::class => [
            'status' => 400,
            'handler' => 'handleValidationException',
        ],
        RepositoryCreateException::class, RepositoryUpdateException::class => [
            'status' => 400,
            'handler' => 'handleRepositoryException',
        ],
    ];

    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $exceptionClass = get_class($throwable);

        if (! isset(self::EXCEPTION_HANDLERS[$exceptionClass])) {
            return $response;
        }

        $this->stopPropagation();
        $handler = self::EXCEPTION_HANDLERS[$exceptionClass];

        if (isset($handler['handler'])) {
            return $this->{$handler['handler']}($throwable, $response);
        }

        return $this->createJsonResponse(
            $response,
            $handler['status'],
            ['message' => $handler['message']]
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function createJsonResponse(ResponseInterface $response, int $statusCode, array $data): ResponseInterface
    {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode)
            ->withBody(new SwooleStream(json_encode($data, JSON_UNESCAPED_UNICODE)));
    }

    private function handleValidationException(
        EntityValidationWithErrorsException $exception,
        ResponseInterface $response
    ): ResponseInterface {
        $errors = $exception->getErrors();
        return $this->createJsonResponse($response, 400, [
            'message' => $this->formatValidationErrorMessage($errors),
            'errors' => $errors,
        ]);
    }

    private function formatValidationErrorMessage(array $errors): string
    {
        if (count($errors) > 1) {
            return __('hf-repository.validation_error_with_count', [
                current($errors)[0],
                count($errors) - 1,
            ]);
        }

        return current($errors)[0];
    }

    private function handleRepositoryException(
        RepositoryCreateException $exception,
        ResponseInterface $response
    ): ResponseInterface {
        return $this->createJsonResponse($response, 400, [
            'message' => $exception->getMessage(),
        ]);
    }

    private function handleRateLimitException(
        RateLimitException $exception,
        ResponseInterface $response
    ): ResponseInterface {
        $message = __('hf-repository.too_many_requests');

        return $this->createJsonResponse($response, 429, [
            'message' => $message,
        ]);
    }
}

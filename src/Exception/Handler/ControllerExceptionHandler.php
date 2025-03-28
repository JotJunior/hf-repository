<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Jot\HfRepository\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\RateLimit\Exception\RateLimitException;
use Jot\HfRepository\Exception\EntityValidationWithErrorsException;
use Jot\HfRepository\Exception\RepositoryCreateException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ControllerExceptionHandler extends ExceptionHandler
{

    private const EXCEPTION_HANDLERS = [
        RateLimitException::class => [
            'status' => 429,
            'message' => 'Too Many Requests.'
        ],
        EntityValidationWithErrorsException::class => [
            'status' => 400,
            'handler' => 'handleValidationException'
        ],
        RepositoryCreateException::class => [
            'status' => 400,
            'handler' => 'handleRepositoryException'
        ]
    ];

    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $exceptionClass = get_class($throwable);

        if (!isset(self::EXCEPTION_HANDLERS[$exceptionClass])) {
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

    private function createJsonResponse(ResponseInterface $response, int $statusCode, array $data): ResponseInterface
    {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode)
            ->withBody(new SwooleStream(json_encode($data, JSON_UNESCAPED_UNICODE)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function handleValidationException(
        EntityValidationWithErrorsException $exception,
        ResponseInterface                   $response
    ): ResponseInterface
    {
        $errors = $exception->getErrors();
        return $this->createJsonResponse($response, 400, [
            'message' => $this->formatValidationErrorMessage($errors),
            'errors' => $errors,
        ]);
    }

    private function formatValidationErrorMessage(array $errors): string
    {
        if (count($errors) > 1) {
            return sprintf('%s (and %d more errors)', current($errors)[0], count($errors) - 1);
        }

        return current($errors)[0];
    }

    private function handleRepositoryException(
        RepositoryCreateException $exception,
        ResponseInterface         $response
    ): ResponseInterface
    {
        return $this->createJsonResponse($response, 400, [
            'message' => $exception->getMessage()
        ]);
    }
}

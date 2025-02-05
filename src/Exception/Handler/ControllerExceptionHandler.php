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
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ControllerExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof RateLimitException) {
            $this->stopPropagation();
            return $this->createJsonResponse($response, 429, ['message' => 'Too Many Requests.']);
        }

        if ($throwable instanceof EntityValidationWithErrorsException) {
            $this->stopPropagation();

            $errors = $throwable->getErrors();
            $message = $this->formatValidationErrorMessage($errors);

            return $this->createJsonResponse($response, 400, [
                'message' => $message,
                'errors' => $errors,
            ]);
        }

        return $response;
    }

    private function createJsonResponse(ResponseInterface $response, int $statusCode, array $data): ResponseInterface
    {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode)
            ->withBody(new SwooleStream(json_encode($data, JSON_UNESCAPED_UNICODE)));
    }

    private function formatValidationErrorMessage(array $errors): string
    {
        if (count($errors) > 1) {
            return sprintf('%s (and %d more errors)', current($errors)[0], count($errors) - 1);
        }

        return current($errors)[0];
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}

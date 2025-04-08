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

namespace Jot\HfRepository\Swagger;

use Hyperf\Engine\Http\Stream;
use Hyperf\HttpMessage\Server\Request as Psr7Request;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\Swagger\HttpServer;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class SwaggerHttpServer extends HttpServer
{
    public function onRequest($request, $response): void
    {
        try {
            if ($request instanceof ServerRequestInterface) {
                $psr7Request = $request;
            } else {
                $psr7Request = Psr7Request::loadFromSwooleRequest($request);
            }

            $path = $psr7Request->getUri()->getPath();
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ($path === $this->config['url']) {
                $stream = new Stream($this->getHtml());
                $contentType = 'text/html;charset=utf-8';
            } else {
                $stream = new Stream($this->getMetadata($path));
                $contentType = match ($extension) {
                    'css' => 'text/css',
                    'jpg', 'jpeg' => $contentType = 'image/jpeg',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'svg' => 'text/xml',
                    'txt' => 'text/plain',
                    default => 'application/json;charset=utf-8'
                };
            }

            $psrResponse = (new Response())->setBody($stream)->setHeader('content-type', $contentType);

            $this->emitter->emit($psrResponse, $response);
        } catch (Throwable) {
            $this->emitter->emit(
                (new Response())
                    ->setBody(new Stream('Server Error'))
                    ->setHeader('content-type', 'text/html;charset=utf-8'),
                $response
            );
        }
    }

    protected function getHtml(): string
    {
        if (! empty($this->config['html'])) {
            return $this->config['html'];
        }

        return is_file(__DIR__ . '/../../storage/swagger/index.html') ? file_get_contents(__DIR__ . '/../../storage/swagger/index.html') : '';
    }
}

<?php

declare(strict_types=1);
/**
 * This file is part of the hf_repository module, a package build for Hyperf framework that is responsible for manage controllers, entities and repositories.
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
            if ($path === $this->config['url']) {
                $stream = new Stream($this->getHtml());
                $contentType = 'text/html;charset=utf-8';
            } elseif (str_ends_with($path, '.css')) {
                $stream = new Stream($this->getMetadata($path));
                $contentType = 'text/css';
            } elseif (str_ends_with($path, '.svg')) {
                $stream = new Stream($this->getMetadata($path));
                $contentType = 'text/xml';
            } elseif (str_ends_with($path, '.js')) {
                $stream = new Stream($this->getMetadata($path));
                $contentType = 'application/javascript';
            } else {
                $stream = new Stream($this->getMetadata($path));
                $contentType = 'application/json;charset=utf-8';
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

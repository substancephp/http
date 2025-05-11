<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AttributeGatheringMiddleware implements MiddlewareInterface
{
    public bool $called = false;

    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $request = $request->withAttribute('attribute gathering middleware called', true);
        $attributes = $request->getAttributes();
        $attributes = \json_encode($attributes, JSON_THROW_ON_ERROR);
        $this->called = true;
        return $this->responseFactory
            ->createResponse()
            ->withHeader('X-Request-Attributes', $attributes);
    }
}

<?php

declare(strict_types=1);

namespace TestUtil\Fixture\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class AttributeGatheringMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $request = $request->withAttribute('attribute gathering middleware called', true);
        $attributes = $request->getAttributes();
        $attributes = \json_encode($attributes, JSON_THROW_ON_ERROR);
        \assert(\is_string($attributes));
        return $this->responseFactory
            ->createResponse()
            ->withHeader('X-Request-Attributes', $attributes);
    }
}

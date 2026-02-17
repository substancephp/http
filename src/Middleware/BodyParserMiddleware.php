<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Util\Request;

class BodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! empty($request->getParsedBody())) {
            return $handler->handle($request);
        }
        $parsedBody = self::parseBody($request);
        if ($parsedBody !== null) {
            $request = $request->withParsedBody($parsedBody);
        }
        return $handler->handle($request);
    }

    /**
     * @return null|array<string, mixed>|object
     * @throws UserError if request is malformed
     */
    private static function parseBody(ServerRequestInterface $request): null|array|object
    {
        switch ($request->getMethod()) {
            case 'PATCH':
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (Request::containsJson($request)) {
                    try {
                        $body = (string) $request->getBody();
                        return \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        UserError::throw(400);
                    }
                }
                return $request->getParsedBody();
            default:
                return null;
        }
    }
}

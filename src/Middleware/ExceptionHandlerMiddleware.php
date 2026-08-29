<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use SubstancePHP\HTTP\ErrorResponseFallbackGeneratorInterface;
use SubstancePHP\HTTP\Exception\BaseException\UserError;
use SubstancePHP\HTTP\Util\Json;

/**
 * Converts exceptions thrown deeper in the middleware stack into HTTP responses.
 *
 * Intended to run first (outermost) in the middleware stack, so that the try/catch wraps the whole
 * stack.
 *
 * - {@see UserError} is converted into a response with the exception's status code and the exception
 *   message as the body.
 * - Any other {@see \Throwable} is converted into a logged 500 response via
 *   {@see ErrorResponseFallbackGeneratorInterface}; the exception message is never exposed to the
 *   client, the body being a generic "Internal Server Error" phrase instead.
 *
 * Error bodies follow the client's `Accept` header: `application/json` yields `{"error": "<message>"}`,
 * `text/html` yields the message HTML-escaped, and anything else (a wildcard `Accept` or no `Accept`
 * header) yields plain text. See {@see self::resolveContentType()}.
 *
 * A correlation id is generated once per request at the start of {@see self::process()}, so that
 * every log line emitted during request handling can carry it. It is attached to the request as the
 * {@see self::CORRELATION_ID_ATTRIBUTE} attribute, so downstream middleware can log with it, and
 * added to every response as the `X-Request-Id` header, so clients can always cite it.
 */
class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    /** The request attribute under which the per-request correlation id is stored. */
    public const CORRELATION_ID_ATTRIBUTE = 'substance.http.correlation-id';

    private const CONTENT_TYPE_JSON = 'application/json';
    private const CONTENT_TYPE_HTML = 'text/html; charset=utf-8';
    private const CONTENT_TYPE_PLAIN = 'text/plain';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ErrorResponseFallbackGeneratorInterface $errorResponseFallbackGenerator,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $correlationId = self::generateCorrelationId();
        $request = $request->withAttribute(self::CORRELATION_ID_ATTRIBUTE, $correlationId);
        try {
            $response = $handler->handle($request);
        } catch (UserError $e) {
            return $this->handleUserError($request, $e, $correlationId);
        } catch (\Throwable $e) {
            return $this->handleUnexpectedError($request, $e, $correlationId);
        }
        return $response->withHeader('X-Request-Id', $correlationId);
    }

    private function handleUserError(
        ServerRequestInterface $request,
        UserError $e,
        string $correlationId,
    ): ResponseInterface {
        $contentType = self::resolveContentType($request);
        $this->logger?->warning("[$correlationId] " . $e->getMessage(), ['exception' => $e]);
        $response = $this->responseFactory
            ->createResponse($e->getStatusCode())
            ->withHeader('Content-Type', $contentType)
            ->withHeader('X-Request-Id', $correlationId);
        $response->getBody()->write(self::renderBody($e->getMessage(), $contentType));
        return $response;
    }

    private function handleUnexpectedError(
        ServerRequestInterface $request,
        \Throwable $e,
        string $correlationId,
    ): ResponseInterface {
        $contentType = self::resolveContentType($request);
        $response = ($this->errorResponseFallbackGenerator)($e, $correlationId);
        $response = $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('X-Request-Id', $correlationId);
        $response->getBody()->write(self::renderBody('Internal Server Error', $contentType));
        return $response;
    }

    private static function resolveContentType(ServerRequestInterface $request): string
    {
        foreach (self::parseAcceptedMediaTypes($request) as $mediaType) {
            if ($mediaType === 'application/json' || $mediaType === 'application/*') {
                return self::CONTENT_TYPE_JSON;
            }
            if ($mediaType === 'text/html' || $mediaType === 'text/*') {
                return self::CONTENT_TYPE_HTML;
            }
        }
        return self::CONTENT_TYPE_PLAIN;
    }

    /**
     * @return string[] media types from the `Accept` header, in client preference order, with
     *   parameters (e.g. q-values) and duplicates stripped.
     */
    private static function parseAcceptedMediaTypes(ServerRequestInterface $request): array
    {
        $accept = $request->getHeaderLine('Accept');
        if ($accept === '') {
            return [];
        }
        $mediaTypes = [];
        foreach (\explode(',', $accept) as $part) {
            $mediaType = \explode(';', $part, 2)[0];
            $mediaType = \strtolower(\trim($mediaType));
            $mediaTypes[$mediaType] = true;
        }
        return \array_keys($mediaTypes);
    }

    private static function renderBody(string $message, string $contentType): string
    {
        if ($contentType === self::CONTENT_TYPE_JSON) {
            return Json::of(['error' => $message]);
        }
        if ($contentType === self::CONTENT_TYPE_HTML) {
            return \htmlspecialchars($message, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        }
        return $message;
    }

    private static function generateCorrelationId(): string
    {
        return \bin2hex(\random_bytes(16));
    }
}

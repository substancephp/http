<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\BaseException;

use SubstancePHP\HTTP\Exception\BaseException;
use Throwable;

/**
 * Intended to be converted by an exception handler into a user-facing error message.
 */
class UserError extends BaseException
{
    private readonly int $statusCode;

    /** In most cases, the {@see self::throw} method should be preferred to using the constructor directly.
     */
    public function __construct(int $statusCode, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    /**
     * @param int $statusCode typically this should be passed one of the standard HTTP status codes; however this
     *   is not strictly necessary. You may pass a non-standard status code together with a message of your choosing.
     * @param ?string $message with which to initialize the exception. If this is omitted or passed null, and the
     *   passed statusCode is a standard HTTP status code, then the exception will be initialized with a message
     *   derived from the standard error phrase for that HTTP status code.
     * @throws self
     */
    public static function throw(int $statusCode, ?string $message = null): never
    {
        throw new self($statusCode, $message ?? match ($statusCode) {
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            103 => 'Early Hints',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => "I'm a teapot",
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Too Early',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
            default => "$statusCode error",
        });
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

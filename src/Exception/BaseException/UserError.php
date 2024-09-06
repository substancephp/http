<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP\Exception\BaseException;

use SubstancePHP\HTTP\Exception\BaseException;
use SubstancePHP\HTTP\Status;
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
        if ($message !== null) {
            throw new self($statusCode, $message);
        }
        $status = Status::tryFromHTTPCode($statusCode);
        if ($status === null) {
            throw new self($statusCode, '');
        }
        throw new self($statusCode, $status->getPhrase());
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

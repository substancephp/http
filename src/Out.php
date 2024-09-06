<?php

declare(strict_types=1);

namespace SubstancePHP\HTTP;

/**
 * Contains data for responding to an application request.
 *
 * Intended to be an abstraction around either JSON or console output, or potentially other forms of output.
 */
readonly class Out
{
    // TODO Consider adding an "errors" field too (with structured errors keyed by field).
    // TODO Consider adding a "meta" field too.
    // TODO Consider allowing arbitrary top-level data (_not_ under "data" key) via an "extra" field.

    /**
     * @param mixed $data the substantive information contained in the output; use this for happy path responses
     *    and not for errors or metadata
     * @param ?string $message a user-friendly error message summarising the outcome of the application's action.
     *    This could be intended for eventual display in a toast, for example. It might be a success message, or
     *    it might be an error message.
     */
    public function __construct(
        private Status $status = Status::OK,
        private mixed $data = null,
        private ?string $message = null,
    ) {
    }

    public static function data(mixed $data, Status $status = Status::OK): self
    {
        return new self($status, $data);
    }

    public static function noContent(): self
    {
        return new self(Status::NO_CONTENT);
    }

    public static function message(string $message, Status $status = Status::OK): self
    {
        return new self(status: $status, message: $message);
    }

    public static function userError(?string $message = null, Status $status = Status::UNPROCESSABLE_ENTITY): self
    {
        return new self(status: $status, message: $message);
    }

    public static function notFound(?string $message = null): self
    {
        return new self(status: Status::NOT_FOUND, message: $message);
    }

    public static function unauthorized(?string $message = null): self
    {
        return new self(status: Status::UNAUTHORIZED, message: $message);
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function getConsoleCode(): int
    {
        return $this->status->getConsoleCode();
    }

    public function getHTTPCode(): int
    {
        return $this->status->getHTTPCode();
    }
}

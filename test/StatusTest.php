<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SubstancePHP\HTTP\Status;

#[CoversClass(Status::class)]
#[CoversMethod(Status::class, 'getHTTPCode')]
#[CoversMethod(Status::class, 'isSuccessful')]
#[CoversMethod(Status::class, 'getConsoleCode')]
#[CoversMethod(Status::class, 'getPhrase')]
class StatusTest extends TestCase
{
    /** @return array<array<Status|int|bool|string>> */
    public static function statusProvider(): array
    {
        return [
            [Status::CONTINUE, 100, true, 0, 'Continue'],
            [Status::SWITCHING_PROTOCOLS, 101, true, 0, 'Switching Protocols'],
            [Status::PROCESSING, 102, true, 0, 'Processing'],
            [Status::OK, 200, true, 0, 'OK'],
            [Status::CREATED, 201, true, 0, 'Created'],
            [Status::ACCEPTED, 202, true, 0, 'Accepted'],
            [Status::NON_AUTHORITATIVE_INFORMATION, 203, true, 0, 'Non-Authoritative Information'],
            [Status::NO_CONTENT, 204, true, 0, 'No Content'],
            [Status::RESET_CONTENT, 205, true, 0, 'Reset Content'],
            [Status::PARTIAL_CONTENT, 206, true, 0, 'Partial Content'],
            [Status::MULTI_STATUS, 207, true, 0, 'Multi-Status'],
            [Status::ALREADY_REPORTED, 208, true, 0, 'Already Reported'],
            [Status::MULTIPLE_CHOICES, 300, true, 0, 'Multiple Choices'],
            [Status::MOVED_PERMANENTLY, 301, true, 0, 'Moved Permanently'],
            [Status::FOUND, 302, true, 0, 'Found'],
            [Status::SEE_OTHER, 303, true, 0, 'See Other'],
            [Status::NOT_MODIFIED, 304, true, 0, 'Not Modified'],
            [Status::USE_PROXY, 305, true, 0, 'Use Proxy'],
            [Status::SWITCH_PROXY, 306, true, 0, 'Switch Proxy'],
            [Status::TEMPORARY_REDIRECT, 307, true, 0, 'Temporary Redirect'],
            [Status::BAD_REQUEST, 400, false, 1, 'Bad Request'],
            [Status::UNAUTHORIZED, 401, false, 1, 'Unauthorized'],
            [Status::PAYMENT_REQUIRED, 402, false, 1, 'Payment Required'],
            [Status::FORBIDDEN, 403, false, 1, 'Forbidden'],
            [Status::NOT_FOUND, 404, false, 1, 'Not Found'],
            [Status::METHOD_NOT_ALLOWED, 405, false, 1, 'Method Not Allowed'],
            [Status::NOT_ACCEPTABLE, 406, false, 1, 'Not Acceptable'],
            [Status::PROXY_AUTHENTICATION_REQUIRED, 407, false, 1, 'Proxy Authentication Required'],
            [Status::REQUEST_TIMEOUT, 408, false, 1, 'Request Timeout'],
            [Status::CONFLICT, 409, false, 1, 'Conflict'],
            [Status::GONE, 410, false, 1, 'Gone'],
            [Status::LENGTH_REQUIRED, 411, false, 1, 'Length Required'],
            [Status::PRECONDITION_FAILED, 412, false, 1, 'Precondition Failed'],
            [Status::REQUEST_ENTITY_TOO_LARGE, 413, false, 1, 'Request Entity Too Large'],
            [Status::URI_TOO_LONG, 414, false, 1,'URI Too Long'],
            [Status::UNSUPPORTED_MEDIA_TYPE, 415, false, 1, 'Unsupported Media Type'],
            [Status::RANGE_NOT_SATISFIABLE, 416, false, 1, 'Range Not Satisfiable'],
            [Status::EXPECTATION_FAILED, 417, false, 1, 'Expectation Failed'],
            [Status::IM_A_TEAPOT, 418, false, 1, "I'm a Teapot"],
            [Status::UNPROCESSABLE_ENTITY, 422, false, 1, 'Unprocessable Entity'],
            [Status::LOCKED, 423, false, 1, 'Locked'],
            [Status::FAILED_DEPENDENCY, 424, false, 1, 'Failed Dependency'],
            [Status::UNORDERED_COLLECTION, 425, false, 1, 'Unordered Collection'],
            [Status::UPGRADE_REQUIRED, 426, false, 1, 'Upgrade Required'],
            [Status::PRECONDITION_REQUIRED, 428, false, 1, 'Precondition Required'],
            [Status::TOO_MANY_REQUESTS, 429, false, 1, 'Too Many Requests'],
            [Status::REQUEST_HEADER_FIELDS_TOO_LARGE, 431, false, 1, 'Request Header Fields Too Large'],
            [Status::UNAVAILABLE_FOR_LEGAL_REASONS, 451, false, 1, 'Unavailable For Legal Reasons'],
            [Status::INTERNAL_SERVER_ERROR, 500, false, 1, 'Internal Server Error'],
            [Status::NOT_IMPLEMENTED, 501, false, 1, 'Not Implemented'],
            [Status::BAD_GATEWAY, 502, false, 1, 'Bad Gateway'],
            [Status::SERVICE_UNAVAILABLE, 503, false, 1, 'Service Unavailable'],
            [Status::GATEWAY_TIMEOUT, 504, false, 1, 'Gateway Timeout'],
            [Status::HTTP_VERSION_NOT_SUPPORTED, 505, false, 1, 'HTTP Version Not Supported'],
            [Status::VARIANT_ALSO_NEGOTIATES, 506, false, 1, 'Variant Also Negotiates'],
            [Status::INSUFFICIENT_STORAGE, 507, false, 1, 'Insufficient Storage'],
            [Status::LOOP_DETECTED, 508, false, 1, 'Loop Detected'],
            [Status::NETWORK_AUTHENTICATION_REQUIRED, 511, false, 1, 'Network Authentication Required'],
        ];
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function allMethods(
        Status $status,
        int $expectedCode,
        bool $expectedSuccessful,
        int $expectedConsoleCode,
        string $expectedPhrase,
    ): void {
        $this->assertSame($expectedCode, $status->getHTTPCode());
        $this->assertSame($expectedSuccessful, $status->isSuccessful());
        $this->assertSame($expectedConsoleCode, $status->getConsoleCode());
        $this->assertSame($expectedPhrase, $status->getPhrase());
    }
}

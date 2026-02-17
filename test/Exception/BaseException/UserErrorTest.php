<?php

declare(strict_types=1);

namespace Test\Exception\BaseException;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SubstancePHP\HTTP\Exception\BaseException\UserError;

#[CoversClass(UserError::class)]
#[CoversMethod(UserError::class, '__construct')]
#[CoversMethod(UserError::class, 'throw')]
#[CoversMethod(UserError::class, 'getStatusCode')]
class UserErrorTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $previous = new \Exception('Woops');
        $userError = new UserError(422, 'Invalid inputs', $previous);
        $this->assertSame('Invalid inputs', $userError->getMessage());
        $this->assertSame(422, $userError->getStatusCode());
        $this->assertSame($previous, $userError->getPrevious());
    }

    #[Test]
    public function throwWithCustomMessage(): void
    {
        try {
            UserError::throw(422, 'Invalid inputs');
        } catch (UserError $userError) {
            $this->assertSame('Invalid inputs', $userError->getMessage());
            $this->assertSame(422, $userError->getStatusCode());
        }

        try {
            UserError::throw(999, '');
        } catch (UserError $userError) {
            $this->assertSame('', $userError->getMessage());
            $this->assertSame(999, $userError->getStatusCode());
        }

        try {
            UserError::throw(999, 'my custom status');
        } catch (UserError $userError) {
            $this->assertSame('my custom status', $userError->getMessage());
            $this->assertSame(999, $userError->getStatusCode());
        }
    }

    #[Test]
    #[TestWith([100, "Continue"])]
    #[TestWith([101, "Switching Protocols"])]
    #[TestWith([102, "Processing"])]
    #[TestWith([103, "Early Hints"])]
    #[TestWith([200, "OK"])]
    #[TestWith([201, "Created"])]
    #[TestWith([202, "Accepted"])]
    #[TestWith([203, "Non-Authoritative Information"])]
    #[TestWith([204, "No Content"])]
    #[TestWith([205, "Reset Content"])]
    #[TestWith([206, "Partial Content"])]
    #[TestWith([207, "Multi-Status"])]
    #[TestWith([208, "Already Reported"])]
    #[TestWith([226, "IM Used"])]
    #[TestWith([300, "Multiple Choices"])]
    #[TestWith([301, "Moved Permanently"])]
    #[TestWith([302, "Found"])]
    #[TestWith([303, "See Other"])]
    #[TestWith([304, "Not Modified"])]
    #[TestWith([307, "Temporary Redirect"])]
    #[TestWith([308, "Permanent Redirect"])]
    #[TestWith([400, "Bad Request"])]
    #[TestWith([401, "Unauthorized"])]
    #[TestWith([402, "Payment Required"])]
    #[TestWith([403, "Forbidden"])]
    #[TestWith([404, "Not Found"])]
    #[TestWith([405, "Method Not Allowed"])]
    #[TestWith([406, "Not Acceptable"])]
    #[TestWith([407, "Proxy Authentication Required"])]
    #[TestWith([408, "Request Timeout"])]
    #[TestWith([409, "Conflict"])]
    #[TestWith([410, "Gone"])]
    #[TestWith([411, "Length Required"])]
    #[TestWith([412, "Precondition Failed"])]
    #[TestWith([413, "Request Entity Too Large"])]
    #[TestWith([414, "Request-URI Too Long"])]
    #[TestWith([415, "Unsupported Media Type"])]
    #[TestWith([416, "Requested Range Not Satisfiable"])]
    #[TestWith([417, "Expectation Failed"])]
    #[TestWith([418, "I'm a teapot"])]
    #[TestWith([421, "Misdirected Request"])]
    #[TestWith([422, "Unprocessable Entity"])]
    #[TestWith([423, "Locked"])]
    #[TestWith([424, "Failed Dependency"])]
    #[TestWith([425, "Too Early"])]
    #[TestWith([426, "Upgrade Required"])]
    #[TestWith([428, "Precondition Required"])]
    #[TestWith([429, "Too Many Requests"])]
    #[TestWith([431, "Request Header Fields Too Large"])]
    #[TestWith([449, "Retry With"])]
    #[TestWith([450, "Blocked by Windows Parental Controls"])]
    #[TestWith([451, "Unavailable For Legal Reasons"])]
    #[TestWith([500, "Internal Server Error"])]
    #[TestWith([501, "Not Implemented"])]
    #[TestWith([502, "Bad Gateway"])]
    #[TestWith([503, "Service Unavailable"])]
    #[TestWith([504, "Gateway Timeout"])]
    #[TestWith([505, "HTTP Version Not Supported"])]
    #[TestWith([506, "Variant Also Negotiates"])]
    #[TestWith([507, "Insufficient Storage"])]
    #[TestWith([508, "Loop Detected"])]
    #[TestWith([509, "Bandwidth Limit Exceeded"])]
    #[TestWith([510, "Not Extended"])]
    #[TestWith([511, "Network Authentication Required"])]
    public function throwWithDefaultMessage(int $statusCode, string $expectedMessage): void
    {
        try {
            UserError::throw($statusCode);
        } catch (UserError $userError) {
            $this->assertSame($expectedMessage, $userError->getMessage());
            $this->assertSame($statusCode, $userError->getStatusCode());
        }
    }

    #[Test]
    public function getStatusCode(): void
    {
        $userError = new UserError(422, 'hi');
        $this->assertEquals(422, $userError->getStatusCode());

        $userError = new UserError(500, 'hi');
        $this->assertEquals(500, $userError->getStatusCode());

        $userError = new UserError(999, 'hi');
        $this->assertEquals(999, $userError->getStatusCode());
    }
}

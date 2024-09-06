<?php

declare(strict_types=1);

namespace Test\Exception\BaseException;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
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
    public function throw(): void
    {
        try {
            UserError::throw(422, 'Invalid inputs');
        } catch (UserError $userError) {
            $this->assertSame('Invalid inputs', $userError->getMessage());
            $this->assertSame(422, $userError->getStatusCode());
        }

        try {
            UserError::throw(422);
        } catch (UserError $userError) {
            $this->assertSame('Unprocessable Entity', $userError->getMessage());
            $this->assertSame(422, $userError->getStatusCode());
        }

        try {
            UserError::throw(999);
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

<?php

declare(strict_types=1);

namespace Test\RequestParams;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SubstancePHP\HTTP\RequestParams\PathParams;
use SubstancePHP\HTTP\Route;
use TestUtil\TestUtil;

#[CoversClass(PathParams::class)]
#[CoversMethod(PathParams::class, 'fromRequest')]
class PathParamsTest extends TestCase
{
    #[Test]
    public function fromRequest(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $pathParams = PathParams::fromRequest($request);
        $this->assertSame([], (array) $pathParams);

        $route = Route::from(TestUtil::getActionFixtureRoot(), 'GET', '/stores/42');
        \assert($route instanceof Route);
        $request = $request->withAttribute(Route::class, $route);
        $pathParams = PathParams::fromRequest($request);
        $this->assertSame(['id' => '42'], (array) $pathParams);
    }
}

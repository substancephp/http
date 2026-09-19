<?php

declare(strict_types=1);

namespace Test\PHPStan;

use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use SubstancePHP\HTTP\PHPStan\ActionDataCollector;
use SubstancePHP\HTTP\PHPStan\ShareCollector;
use SubstancePHP\HTTP\PHPStan\TemplateSelectionCollector;
use SubstancePHP\HTTP\PHPStan\TemplateVariablesCollector;
use SubstancePHP\HTTP\PHPStan\TemplateVariablesRule;

/** @extends RuleTestCase<TemplateVariablesRule> */
final class TemplateVariablesRuleTest extends RuleTestCase
{
    private const DATA = __DIR__ . '/data/template-variables';

    #[\Override]
    protected function getRule(): TemplateVariablesRule
    {
        return new TemplateVariablesRule();
    }

    /** @return array<Collector<Node, mixed>> */
    #[\Override]
    protected function getCollectors(): array
    {
        return [
            new TemplateVariablesCollector(),
            new ActionDataCollector(),
            new ShareCollector(),
            new TemplateSelectionCollector(),
        ];
    }

    #[Test]
    public function flagsAVariableNoReturnProvides(): void
    {
        $this->analyse(
            [
                self::DATA . '/actions/stores.get.php',
                self::DATA . '/templates/stores.html.php',
            ],
            [[
                \sprintf(
                    'This return does not provide $title, which the template %s declares.',
                    self::DATA . '/templates/stores.html.php',
                ),
                6,
            ]],
        );
    }

    #[Test]
    public function allowsVariablesProvidedByTheApplicationShare(): void
    {
        $this->analyse(
            [
                self::DATA . '/actions/greeting.get.php',
                self::DATA . '/templates/greeting.html.php',
                self::DATA . '/AppShared.php',
            ],
            [],
        );
    }

    #[Test]
    public function flagsATemplateSelectedDynamically(): void
    {
        $this->analyse(
            [
                self::DATA . '/actions/dynamic.get.php',
                self::DATA . '/templates/dynamic.html.php',
            ],
            [[
                'setTemplate() needs a literal or constant template name, so that the templates an action renders'
                . ' can be checked.',
                8,
            ]],
        );
    }
}

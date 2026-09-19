<?php

declare(strict_types=1);

namespace Test\PHPStan;

use PhpParser\Node;
use PHPStan\Collectors\Collector;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use SubstancePHP\HTTP\PHPStan\ActionDataCollector;
use SubstancePHP\HTTP\PHPStan\IncludeSiteCollector;
use SubstancePHP\HTTP\PHPStan\ShareCollector;
use SubstancePHP\HTTP\PHPStan\TemplateSelectionCollector;
use SubstancePHP\HTTP\PHPStan\TemplateSetCollector;
use SubstancePHP\HTTP\PHPStan\TemplateVariablesCollector;
use SubstancePHP\HTTP\PHPStan\TemplateVariablesRule;

/** @extends RuleTestCase<TemplateVariablesRule> */
final class TemplateVariablesRuleTest extends RuleTestCase
{
    private const DATA = __DIR__ . '/data/template-variables';

    #[\Override]
    protected function getRule(): TemplateVariablesRule
    {
        return new TemplateVariablesRule(self::getContainer()->getByType(TypeStringResolver::class));
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
            new TemplateSetCollector(),
            new IncludeSiteCollector(),
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

    #[Test]
    public function checksATemplateAnActionDeclaresAsAnAlternative(): void
    {
        $this->analyse(
            [
                self::DATA . '/actions/catalog.get.php',
                self::DATA . '/templates/catalog.html.php',
                self::DATA . '/templates/catalog-empty.html.php',
            ],
            [[
                \sprintf(
                    'No return of this action provides $message, which the template %s declares.',
                    self::DATA . '/templates/catalog-empty.html.php',
                ),
                8,
            ]],
        );
    }

    #[Test]
    public function flagsAVariableOfTheWrongType(): void
    {
        $this->analyse(
            [
                self::DATA . '/actions/summary.get.php',
                self::DATA . '/templates/summary.html.php',
            ],
            [[
                \sprintf(
                    'This return provides $count as \'twelve\', but the template %s declares int.',
                    self::DATA . '/templates/summary.html.php',
                ),
                6,
            ]],
        );
    }

    #[Test]
    public function checksWhatAnIncludePasses(): void
    {
        $this->analyse(
            [
                self::DATA . '/includes-detail.html.php',
                self::DATA . '/partials/detail.html.php',
            ],
            [[
                \sprintf(
                    'This passes $count as \'many\', but %s declares int.',
                    self::DATA . '/partials/detail.html.php',
                ),
                10,
            ]],
        );
    }

    #[Test]
    public function flagsVariablesNoSingleReturnProvidesTogether(): void
    {
        $this->analyse(
            [
                self::DATA . '/actions/combined.get.php',
                self::DATA . '/templates/combined.html.php',
                self::DATA . '/templates/combined-alt.html.php',
            ],
            [[
                \sprintf(
                    'No return of this action provides $items, $message together, which the template %s declares.',
                    self::DATA . '/templates/combined.html.php',
                ),
                8,
            ]],
        );
    }
}

<?php

declare(strict_types=1);

namespace Rector\Symfony\Symfony52\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use Rector\Core\PhpParser\Node\Value\ValueResolver;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://github.com/symfony/symfony/blob/5.x/UPGRADE-5.2.md#dependencyinjection
 * @see \Rector\Symfony\Tests\Symfony52\Rector\MethodCall\DefinitionAliasSetPrivateToSetPublicRector\DefinitionAliasSetPrivateToSetPublicRectorTest
 */
final class DefinitionAliasSetPrivateToSetPublicRector extends AbstractRector
{
    /**
     * @var ObjectType[]
     */
    private array $definitionObjectTypes = [];

    public function __construct(
        private readonly ValueResolver $valueResolver
    ) {
        $this->definitionObjectTypes = [
            new ObjectType('Symfony\Component\DependencyInjection\Alias'),
            new ObjectType('Symfony\Component\DependencyInjection\Definition'),
        ];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Migrates from deprecated Definition/Alias->setPrivate() to Definition/Alias->setPublic()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;

class SomeClass
{
    public function run()
    {
        $definition = new Definition('Example\Foo');
        $definition->setPrivate(false);

        $alias = new Alias('Example\Foo');
        $alias->setPrivate(false);
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;

class SomeClass
{
    public function run()
    {
        $definition = new Definition('Example\Foo');
        $definition->setPublic(true);

        $alias = new Alias('Example\Foo');
        $alias->setPublic(true);
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'setPrivate')) {
            return null;
        }

        if (! $this->nodeTypeResolver->isObjectTypes($node->var, $this->definitionObjectTypes)) {
            return null;
        }

        $argValue = $node->getArgs()[0]
            ->value;

        $argValue = $argValue instanceof ConstFetch
            ? $this->createNegationConsFetch($argValue)
            : new BooleanNot($argValue);

        return $this->nodeFactory->createMethodCall($node->var, 'setPublic', [$argValue]);
    }

    private function createNegationConsFetch(ConstFetch $constFetch): ConstFetch
    {
        if ($this->valueResolver->isFalse($constFetch)) {
            return $this->nodeFactory->createTrue();
        }

        return $this->nodeFactory->createFalse();
    }
}

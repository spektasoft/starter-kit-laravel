<?php

namespace App\Rules\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticCall>
 */
// @intelephense-disable
class NoDirectRichEditorMakeRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @return array<int, RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->class instanceof Name) {
            return [];
        }

        $className = $node->class->toString();
        $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : '';

        if ($className === 'Filament\\Forms\\Components\\RichEditor' && $methodName === 'make') {
            return [
                RuleErrorBuilder::message(
                    'Use App\\Filament\\Forms\\Components\\CuratorEnabledRichEditor::make() instead of RichEditor::make() to ensure Curator media integration.'
                )->build(),
            ];
        }

        return [];
    }
}

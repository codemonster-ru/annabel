<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Compilation;

use Closure;
use Codemonster\Razor\Components\Parsing\ComponentInvocation;

final readonly class ComponentInvocationCompiler
{
    public function __construct(private ComponentPropCompiler $props)
    {
    }

    /** @param Closure(string): string $compileContent */
    public function compile(ComponentInvocation $invocation, Closure $compileContent): string
    {
        $slots = [];

        if ($invocation->content !== null) {
            $slots[] = var_export('default', true) . ' => ' . $this->compileSlot($invocation->content, $compileContent);
        }

        return '<?= $__razor->renderComponent('
            . var_export($invocation->tag, true)
            . ', '
            . $this->props->compile($invocation->attributes)
            . ', ['
            . implode(', ', $slots)
            . ']) ?>';
    }

    /** @param Closure(string): string $compileContent */
    private function compileSlot(string $content, Closure $compileContent): string
    {
        return '$__razor->componentSlot(get_defined_vars(), '
            . 'static function (array $__razorSlotScope) use ($__razor): void {'
            . ' extract($__razorSlotScope, EXTR_SKIP); ?>'
            . $compileContent($content)
            . '<?php })';
    }
}

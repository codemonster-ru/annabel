<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Compilation;

use Closure;
use Codemonster\Razor\Components\Parsing\ComponentInvocation;
use Codemonster\Razor\Components\Parsing\ComponentSlotParser;

final readonly class ComponentInvocationCompiler
{
    public function __construct(
        private ComponentPropCompiler $props,
        private ComponentSlotParser $slots,
    ) {
    }

    /** @param Closure(string): string $compileContent */
    public function compile(ComponentInvocation $invocation, Closure $compileContent): string
    {
        $slots = [];

        if ($invocation->content !== null) {
            $parsed = $this->slots->parse($invocation->content);
            $slots[] = var_export('default', true) . ' => ' . $this->compileSlot($parsed->default, $compileContent);

            foreach ($parsed->named as $name => $content) {
                $slots[] = var_export($name, true) . ' => ' . $this->compileSlot($content, $compileContent);
            }
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

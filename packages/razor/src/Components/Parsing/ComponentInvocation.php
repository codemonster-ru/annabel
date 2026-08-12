<?php

declare(strict_types=1);

namespace Codemonster\Razor\Components\Parsing;

final readonly class ComponentInvocation
{
    public function __construct(
        public string $tag,
        public string $attributes,
        public ?string $content,
        public int $startOffset,
        public int $endOffset,
    ) {
    }

    public function isSelfClosing(): bool
    {
        return $this->content === null;
    }
}

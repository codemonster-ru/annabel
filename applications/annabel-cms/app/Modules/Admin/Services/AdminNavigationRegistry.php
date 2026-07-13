<?php

namespace Codemonster\Cms\Modules\Admin\Services;

use Codemonster\Cms\Modules\Core\ModuleManager;

class AdminNavigationRegistry
{
    /** @var array<string, array{id: string, label: string, parent?: string, href?: string, order?: int}>|null */
    private ?array $items = null;

    public function __construct(
        private ModuleManager $modules,
    ) {
    }

    /**
     * @return array<int, array{value: string, label: string, href?: string, children?: array<mixed>}>
     */
    public function navigation(): array
    {
        return $this->children($this->items(), null);
    }

    public function label(string $value): ?string
    {
        return $this->items()[$value]['label'] ?? null;
    }

    /**
     * @return array<string, array{id: string, label: string, parent?: string, href?: string, order?: int}>
     */
    private function items(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $items = [];
        $hrefs = [];

        foreach ($this->modules->definitions() as $module) {
            $admin = $module->metadata['admin'] ?? [];

            if (!is_array($admin)) {
                throw new \RuntimeException("Admin configuration must be an array: {$module->name}");
            }

            $navigation = $admin['navigation'] ?? [];

            if (!is_array($navigation)) {
                throw new \RuntimeException("Admin navigation must be an array: {$module->name}");
            }

            foreach ($navigation as $item) {
                $item = $this->parseItem($item, $module->name);
                $id = $item['id'];

                if (isset($items[$id])) {
                    throw new \RuntimeException("Duplicate admin navigation item: {$id}");
                }

                $href = $item['href'] ?? null;

                if ($href !== null && isset($hrefs[$href])) {
                    throw new \RuntimeException("Duplicate admin navigation href: {$href}");
                }

                $items[$id] = $item;

                if ($href !== null) {
                    $hrefs[$href] = true;
                }
            }
        }

        foreach ($items as $item) {
            $parent = $item['parent'] ?? null;

            if ($parent !== null && !isset($items[$parent])) {
                throw new \RuntimeException("Admin navigation parent is missing: {$parent}");
            }
        }

        $this->assertNoCycles($items);

        return $this->items = $items;
    }

    /**
     * @return array{id: string, label: string, parent?: string, href?: string, order?: int}
     */
    private function parseItem(mixed $item, string $module): array
    {
        if (!is_array($item)) {
            throw new \RuntimeException("Admin navigation item must be an array: {$module}");
        }

        $id = $item['id'] ?? null;
        $label = $item['label'] ?? null;
        $parent = $item['parent'] ?? null;
        $href = $item['href'] ?? null;
        $order = $item['order'] ?? 0;

        if (!is_string($id) || $id === '' || !is_string($label) || $label === '') {
            throw new \RuntimeException("Admin navigation item requires id and label: {$module}");
        }

        if ($parent !== null && (!is_string($parent) || $parent === '')) {
            throw new \RuntimeException("Admin navigation parent must be a non-empty string: {$module}");
        }

        if ($href !== null && (!is_string($href) || $href === '')) {
            throw new \RuntimeException("Admin navigation href must be a non-empty string: {$module}");
        }

        if (!is_int($order)) {
            throw new \RuntimeException("Admin navigation order must be an integer: {$module}");
        }

        return array_filter([
            'id' => $id,
            'label' => $label,
            'parent' => $parent,
            'href' => $href,
            'order' => $order,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param array<string, array{id: string, label: string, parent?: string, href?: string, order?: int}> $items
     */
    private function assertNoCycles(array $items): void
    {
        $states = [];

        $visit = function (string $id) use (&$visit, &$states, $items): void {
            if (($states[$id] ?? null) === 'complete') {
                return;
            }

            if (($states[$id] ?? null) === 'visiting') {
                throw new \RuntimeException("Circular admin navigation detected at: {$id}");
            }

            $states[$id] = 'visiting';
            $parent = $items[$id]['parent'] ?? null;

            if ($parent !== null) {
                $visit($parent);
            }

            $states[$id] = 'complete';
        };

        foreach (array_keys($items) as $id) {
            $visit($id);
        }
    }

    /**
     * @param array<string, array{id: string, label: string, parent?: string, href?: string, order?: int}> $items
     * @return array<int, array{value: string, label: string, href?: string, children?: array<mixed>}>
     */
    private function children(array $items, ?string $parent): array
    {
        $children = array_filter(
            $items,
            static fn (array $item): bool => ($item['parent'] ?? null) === $parent,
        );

        uasort($children, static fn (array $left, array $right): int => [
            $left['order'] ?? 0,
            $left['id'],
        ] <=> [
            $right['order'] ?? 0,
            $right['id'],
        ]);

        $result = [];

        foreach ($children as $item) {
            $entry = [
                'value' => $item['id'],
                'label' => $item['label'],
            ];

            if (isset($item['href'])) {
                $entry['href'] = $item['href'];
            }

            $nested = $this->children($items, $item['id']);

            if ($nested !== []) {
                $entry['children'] = $nested;
            }

            $result[] = $entry;
        }

        return $result;
    }
}

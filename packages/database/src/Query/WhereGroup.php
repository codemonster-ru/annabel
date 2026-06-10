<?php

namespace Codemonster\Database\Query;

class WhereGroup
{
    /**
     * @var list<
     *     array{type: 'condition', boolean: string, condition: WhereCondition}
     *     |array{type: 'group', boolean: string, group: WhereGroup}
     * >
     */
    public array $items = [];

    public function addCondition(WhereCondition $cond): void
    {
        $this->items[] = [
            'type' => 'condition',
            'boolean' => $cond->boolean,
            'condition' => $cond,
        ];
    }

    public function addGroup(WhereGroup $group, string $boolean): void
    {
        $this->items[] = [
            'type' => 'group',
            'boolean' => strtoupper($boolean),
            'group' => $group,
        ];
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}

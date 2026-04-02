<?php

namespace Guava\FilamentDrafts\Tests\Fixtures;

class FakeRevisionQuery
{
    public function __construct(
        protected array &$updates,
    ) {}

    public function where(string $column, mixed $value): static
    {
        $this->updates['where'][] = [$column, $value];

        return $this;
    }

    public function update(array $attributes): int
    {
        $this->updates['update'][] = $attributes;

        return 1;
    }
}

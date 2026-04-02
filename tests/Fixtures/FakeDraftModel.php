<?php

namespace Guava\FilamentDrafts\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class FakeDraftModel extends Model
{
    protected $guarded = [];

    public bool $publishedState = true;

    public bool $currentState = true;

    public array $savedPayload = [];

    public array $updatedPayload = [];

    public array $draftPayload = [];

    public array $revisionUpdates = [];

    public bool $withoutRevisionCalled = false;

    public bool $updateCalled = false;

    public bool $updateAsDraftCalled = false;

    public static function withoutTimestamps(callable $callback): mixed
    {
        return $callback();
    }

    public function isPublished(): bool
    {
        return $this->publishedState;
    }

    public function withoutRevision(): static
    {
        $this->withoutRevisionCalled = true;

        return $this;
    }

    public function save(array $options = []): bool
    {
        $this->savedPayload = $this->attributesToArray();

        return true;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->updateCalled = true;
        $this->updatedPayload = $attributes;
        $this->fill($attributes);

        return true;
    }

    public function updateAsDraft(array $attributes): bool
    {
        $this->updateAsDraftCalled = true;
        $this->draftPayload = $attributes;

        return true;
    }

    public function revisions(): FakeRevisionQuery
    {
        return new FakeRevisionQuery($this->revisionUpdates);
    }
}

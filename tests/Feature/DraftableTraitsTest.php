<?php

namespace Guava\FilamentDrafts\Tests\Feature;

use Filament\Actions\Action;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Guava\FilamentDrafts\Admin\Resources\Pages\Create\Draftable as CreateDraftable;
use Guava\FilamentDrafts\Admin\Resources\Pages\Edit\Draftable as EditDraftable;
use Guava\FilamentDrafts\Admin\Resources\Pages\List\Draftable as ListDraftable;
use Guava\FilamentDrafts\Tests\Fixtures\FakeDraftModel;
use Guava\FilamentDrafts\Tests\TestCase;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

class DraftableTraitsTest extends TestCase
{
    public function test_create_trait_saves_draft_as_unpublished_record(): void
    {
        $page = new class
        {
            use CreateDraftable;

            public function getModel(): string
            {
                return FakeDraftModel::class;
            }

            protected function getCreateFormAction(): Action
            {
                return Action::make('create');
            }

            protected function getCreateAnotherFormAction(): Action
            {
                return Action::make('createAnother');
            }

            protected function getFormActions(): array
            {
                return [Action::make('create')];
            }

            public function createRecord(array $data): FakeDraftModel
            {
                /** @var FakeDraftModel $record */
                $record = $this->handleRecordCreation($data);

                return $record;
            }
        };

        $page->shouldSaveAsDraft = true;

        $record = $page->createRecord(['title' => 'Draft']);

        assertTrue($record->withoutRevisionCalled);
        assertSame(['title' => 'Draft', 'is_published' => false], $record->savedPayload);
    }

    public function test_edit_trait_updates_published_record_as_draft(): void
    {
        $page = new class
        {
            use EditDraftable;

            public array $events = [];

            public FakeDraftModel $record;

            public function __construct()
            {
                $this->record = new FakeDraftModel();
                $this->record->id = 10;
                $this->record->publishedState = true;
            }

            public function getRecord(): FakeDraftModel
            {
                return $this->record;
            }

            public function getResource(): string
            {
                return FakeResource::class;
            }

            public function dispatch(string $event, mixed ...$payload): void
            {
                $this->events[] = [$event, $payload];
            }

            protected function getSaveFormAction(): Action
            {
                return Action::make('save');
            }

            protected function getFormActions(): array
            {
                return [Action::make('save')];
            }

            protected function getSavedNotification(): ?object
            {
                return (object) ['ok' => true];
            }

            public function updateRecord(array $data): FakeDraftModel
            {
                /** @var FakeDraftModel $record */
                $record = $this->handleRecordUpdate($this->record, $data);

                return $record;
            }

            public function savedTitle(): ?string
            {
                return $this->getSavedNotificationTitle();
            }
        };

        $page->shouldSaveAsDraft = true;

        $record = $page->updateRecord(['title' => 'Updated draft']);

        assertTrue($record->updateAsDraftCalled);
        assertSame(['title' => 'Updated draft'], $record->draftPayload);
        assertSame([['updateRevisions', [10]]], $page->events);
        assertSame('Draft saved', $page->savedTitle());
    }

    public function test_edit_trait_unpublishes_other_revisions_when_publishing_current_revision(): void
    {
        $page = new class
        {
            use EditDraftable;

            public array $events = [];

            public FakeDraftModel $record;

            public function __construct()
            {
                $this->record = new FakeDraftModel();
                $this->record->id = 11;
                $this->record->publishedState = false;
                $this->record->currentState = false;
                $this->record->is_current = false;
            }

            public function getRecord(): FakeDraftModel
            {
                return $this->record;
            }

            public function getResource(): string
            {
                return FakeResource::class;
            }

            public function dispatch(string $event, mixed ...$payload): void
            {
                $this->events[] = [$event, $payload];
            }

            protected function getSaveFormAction(): Action
            {
                return Action::make('save');
            }

            protected function getFormActions(): array
            {
                return [Action::make('save')];
            }

            protected function getSavedNotification(): ?object
            {
                return (object) ['ok' => true];
            }

            public function updateRecord(array $data): FakeDraftModel
            {
                /** @var FakeDraftModel $record */
                $record = $this->handleRecordUpdate($this->record, $data);

                return $record;
            }
        };

        $record = $page->updateRecord(['title' => 'Published']);

        assertTrue($record->updateCalled);
        assertSame(['title' => 'Published', 'is_published' => true], $record->updatedPayload);
        assertSame([['is_published', true]], $record->revisionUpdates['where']);
        assertSame([['is_published' => false]], $record->revisionUpdates['update']);
        assertSame([['updateRevisions', [11]]], $page->events);
    }

    public function test_list_trait_returns_all_and_draft_tabs(): void
    {
        $page = new class
        {
            use ListDraftable;
        };

        $tabs = $page->getTabs();

        assertCount(2, $tabs);
        assertSame(['all', 'drafts'], array_keys($tabs));
    }

    public function test_edit_trait_registers_scoped_render_hook(): void
    {
        $page = new class
        {
            use EditDraftable;

            public FakeDraftModel $record;

            public function __construct()
            {
                $this->record = new FakeDraftModel();
            }

            public function getRecord(): FakeDraftModel
            {
                return $this->record;
            }

            public function getResource(): string
            {
                return FakeResource::class;
            }

            protected function getSaveFormAction(): Action
            {
                return Action::make('save');
            }

            protected function getFormActions(): array
            {
                return [Action::make('save')];
            }

            protected function getSavedNotification(): ?object
            {
                return null;
            }
        };

        $page->renderingDraftable();

        $hook = FilamentView::renderHook(PanelsRenderHook::CONTENT_END, scopes: $page::class);

        assertStringContainsString('filament-drafts::revisions-paginator', $hook);
    }
}

class FakeResource
{
    public static function getUrl(string $name, array $parameters = []): string
    {
        return '/resources/' . $name;
    }
}

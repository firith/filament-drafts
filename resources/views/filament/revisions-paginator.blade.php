<div
    class="mt-4"
    x-data
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-drafts-styles', package: 'guava/filament-drafts'))]"
>
	<hr />
	<livewire:filament-drafts::revisions-paginator
			:resource="$resource"
			:record="$record"
	/>
</div>

<?php

namespace Guava\FilamentDrafts;

use Guava\FilamentDrafts\Tables\Http\Livewire\RevisionsPaginator;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentDraftsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-drafts';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews(static::$name);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-drafts-styles', __DIR__ . '/../dist/plugin.css')->loadedOnRequest(),
        ], package: 'guava/filament-drafts');

        Livewire::component('filament-drafts::revisions-paginator', RevisionsPaginator::class);
    }
}

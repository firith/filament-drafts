# Filament Drafts v4 kompatibilitási terv

## Összefoglaló
A plugin kapjon külön `v4` major kiadást, ne közös `v3+v4` kódbázist. A cél az, hogy a jelenlegi publikus használat nagy része változatlan maradjon a fogyasztó oldalon, miközben a belső integráció teljesen Filament 4-re áll át: composer-korlátok, action namespace-ek, render hook regisztráció, asset bootstrapping és a build pipeline.

Alap a hivatalos Filament 4 upgrade guide szerint: PHP 8.2+, Laravel 11.28+, valamint custom theme esetén Tailwind CSS 4.1+ szükséges. A plugin oldali asset-regisztrációt a Filament plugin guide alapján `packageBooted()`-ba kell tenni, a render hookokat pedig a v4-es `FilamentView` / `PanelsRenderHook` API-ra kell átállítani.
Források: [Upgrade guide](https://filamentphp.com/docs/4.x/upgrade-guide), [Render hooks](https://filamentphp.com/docs/4.x/advanced/render-hooks), [Plugin getting started](https://filamentphp.com/docs/5.x/plugins/getting-started), [Standalone plugin guide](https://filamentphp.com/docs/4.x/plugins/building-a-standalone-plugin), [Actions overview](https://filamentphp.com/docs/4.x/actions/overview)

## Fő változtatások
- `composer.json`
    - `filament/filament` függőség `^4.0`.
    - PHP minimum `^8.2`.
    - Laravel kompatibilitás Filament 4 minimumaihoz igazítva.
    - `orchestra/testbench` verzió frissítése a Laravel 11-es tesztmátrixhoz.
    - A kiadás legyen külön major, például `2.x` vagy `4.x` ágverzió, a README-ben egyértelmű v3/v4 verziótáblával.

- Service provider és asset-ek
    - A jelenlegi `PackageServiceProvider` marad, de az asset-regisztráció menjen `packageBooted()`-ba.
    - A CSS asset kapjon `loadedOnRequest()` alapú regisztrációt, hogy csak ott töltődjön, ahol a paginator tényleg megjelenik.
    - A `DraftableTable` import törlendő vagy pótlandó, mert jelenleg lógó referencia.

- Filament action API
    - Minden `Filament\Pages\Actions\Action` import cseréje `Filament\Actions\Action`-ra.
    - A három custom action (`SaveDraftAction`, `PublishAction`, `UnpublishAction`) maradjon külön osztályban, de v4 action API-ra igazítva.
    - A create/edit page trait-ek action-beillesztése maradjon ugyanazzal a UX-szel: elsődleges publish/save action, mellette draft és unpublish action.

- Render hook és oldalintegráció
    - A `Filament::registerRenderHook()` használat cseréje `Filament\Support\Facades\FilamentView::registerRenderHook()`-ra.
    - A hook ne globálisan regisztrálódjon minden rendernél, hanem a megfelelő edit oldalra legyen scope-olva `PanelsRenderHook::CONTENT_END` használatával.
    - A revízió-paginator továbbra is külön Livewire komponens maradjon, mert a mostani funkcióhoz elég és kisebb átépítés, mint widgetre váltani.

- Blade és frontend réteg
    - A plugin build pipeline frissítése Tailwind 4.1+-re.
    - A `filament-purge` lépést felül kell vizsgálni: ha a jelenlegi purge csomag csak v3-at támogat, cserélni kell vagy el kell hagyni.
    - A Blade-ekben használt Filament komponensek (`x-filament::tabs`, `x-filament::icon`) v4 kompatibilitását ellenőrizni kell, és ahol kell, új komponens API-ra cserélni.
    - A paginator markup maradjon funkcionálisan azonos, csak a v4 CSS/token eltérésekhez igazítva.

- Publikus plugin API
    - Meg kell tartani a jelenlegi trait belépési pontokat:
        - `Guava\FilamentDrafts\Concerns\HasDrafts`
        - resource-level `Draftable`
        - create/edit/list page `Draftable` trait-ek
    - Nem tervezünk új plugin object vagy panel registration API-t, mert ez standalone package-ként jelenleg nem szükséges.
    - A README usage példák maradjanak ugyanazon a mentális modellen, csak v4 importokkal és minimum verziókkal.

## Implementációs részletek
- Edit page trait
    - A `handleRecordUpdate()` logika maradjon változatlan üzleti szemantikával.
    - A `dispatch('updateRevisions', ...)` esemény maradjon, ha a Livewire 3-as komponens ezzel stabilan működik.
    - A saved notification szövegek kerüljenek translation file-ba, ne maradjanak hardcode-olva angolul.

- Create page trait
    - A `shouldSaveAsDraft` viselkedés maradjon.
    - A create és create-another action label-jei továbbra is a publish fogalmat tükrözzék.

- List page trait
    - A tab API v4 kompatibilitását ellenőrizni kell; ha a namespace vagy metódus-aláírás változott, arra kell átállni.
    - A query logika maradjon azonos: `withoutDrafts()` az alap lista, `onlyDrafts()->where('is_current', true)` a draft fülön.

- Livewire komponens
    - A `RevisionsPaginator` maradjon stateful komponens.
    - A redirect maradjon `navigate: true`, ha a v4 panel navigációval együtt tesztelve működik; ellenkező esetben sima redirectre kell egyszerűsíteni.
    - A komponens eseményhallgatása legyen egységesen a Livewire 3 ajánlott mintája szerint.

## Tesztterv
- Új teszt setup Testbench-csel, minimálisan egy demo draftable modellel és erőforrással.
- Lefedendő esetek:
    - create oldalon `Save draft` valóban nem publikál.
    - create oldalon `Publish` publikál.
    - edit oldalon publikált rekord mentése draftként új draft vagy új revízió logikát követ.
    - edit oldalon `Unpublish` lenullázza az aktív publikált állapotot.
    - list tabs helyes rekordhalmazt mutatnak.
    - revisions paginator publikált, draft és korábbi revízió darabszámai helyesek.
    - asset és view betöltés nem törik akkor sem, ha a paginator nincs jelen.
- Manuális smoke test:
    - friss Laravel 11 + Filament 4 sandbox appban install.
    - README szerinti bekötés.
    - create/edit/list oldalak végigkattintása.
    - sötét és világos téma gyors ellenőrzése a paginatoron.

## Alapértelmezések
- Külön Filament 4 major release készül, a v3 támogatás külön ágon marad.
- A publikus trait-alapú integráció nem változik, csak a belső implementáció.
- Nem vezetünk be új konfigurációs fájlt vagy adatbázis migrációt.
- A plugin továbbra is standalone package marad, nem panel-plugin object alapú csomag lesz.
- A Tailwind 4 frissítés kötelező része a munkának, mert a plugin saját CSS-sel rendelkezik.

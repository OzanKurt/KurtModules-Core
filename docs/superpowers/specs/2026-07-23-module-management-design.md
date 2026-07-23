# Module Management Layer - Tasarım Dokümanı

- **Tarih:** 2026-07-23
- **Paketler:** `ozankurt/laravel-modules-core` (contract + registry) + yeni `ozankurt/laravel-modules-manager` (DB + API)
- **Durum:** Tasarım onaylandı, implementasyon planı bekliyor
- **Teslim modu:** headless + API (Filament/UI yok)

## 1. Amaç

KurtModules ailesi (core + 10 modül) için, tüketici uygulamanın modülleri **runtime'da yönetebildiği** bir katman: hangi modül açık, modül feature flag'leri, ve modül ayarları. Yönetim **global default + per-scope override** olarak, headless servis veya REST API üzerinden yapılır. Filament/UI kapsam dışı.

Mevcut zemin (core): `HttpMode` (config-driven per-module yükleme modu), `InteractsWithModuleConfig`, `UserResolver`, `PackageServiceProvider`. Bu katman bunların **üstüne** oturur; DB-backed state/feature/ayar + registry ekler.

## 2. İki katmanlı ayrım

**Core (contract + registry, DB YOK - yalın kalır):**
- `ModuleManifest` value object (fluent builder) + `ModuleRegistry` contract.
- Her modülün ServiceProvider'ı `moduleManifest()` ile kendini declare eder.
- Core boot'ta tüm kayıtlı provider'lardan manifest'leri toplayıp **in-memory registry** kurar. "Ne var ve defaultları ne" için tek doğruluk kaynağı. Tablo/model yok.

**`laravel-modules-manager` (yeni paket, namespace `Kurt\Modules\Manager`):**
- DB tabloları + modeller, `ModuleManager` servisi, `ScopeResolver` contract, REST API (HttpMode gate'li). Core'u require eder.
- Bağımlılık yönü: manager → core. Modüller sadece core'un contract'ını bilir, manager'ı bilmez.

## 3. Manifest (modül kendini nasıl tanıtır)

Core `PackageServiceProvider` tabanına eklenen metod:

```php
protected function moduleManifest(): ModuleManifest
{
    return ModuleManifest::make('blog')
        ->name('Blog')
        ->version('1.0.0')
        ->description('...')
        ->dependsOn('interactions')
        ->feature('comments', default: true)
        ->feature('reactions', default: false)
        ->setting('posts_per_page', default: 15, type: 'int');
}
```

Fluent builder (keşfedilebilir, tip-güvenli, IDE dostu). Registry bunları toplar.

**Disiplin:** manifest'te declare edilmemiş modül/feature/setting DB'de set edilemez → drift engellenir, registry otorite.

## 4. Çözümleme ve ModuleManager

`ModuleManager` (manager paketi) etkin değeri şu zincirle çözer:

```
scope satırı → global satır → manifest default → hard default
```

- `enabled(slug, scope=null): bool`
- `feature(slug, key, scope=null): bool`
- `setting(slug, key, scope=null): mixed`

**Enabled default:** declare edilmiş (kurulu) bir modül varsayılan olarak **açıktır** (hard default = `true`); yani DB satırı yoksa modül enabled. Opt-in modüller manifest'te `->enabledByDefault(false)` ile bunu tersine çevirebilir. Feature/setting hard default'ları ise manifest'teki değerdir (feature yoksa `false`, setting yoksa `null`).

Scope explicit verilmezse `ScopeResolver::current()` ile alınır (null → global). `ScopeResolver`, core'daki `UserResolver` gibi tüketicinin implemente ettiği bir contract - böylece herhangi bir tenancy paketine bağlanmadan generic kalır.

Yazma: `setEnabled/setFeature/setSetting`, global veya belirli scope için DB'ye yazar.

**Cache:** etkin değerler `(scope, slug)` başına cache'lenir, yazımda invalidate edilir - her request'te DB'ye gidilmez. Cache okuması fail-open.

## 5. "Açık/kapalı" semantiği (boot-time gerçeği)

Provider'lar **her zaman register olur** (binding/migration var olsun). Bir modülün route/API yüzeyi request anında `EnsureModuleEnabled($slug)` middleware'i ile korunur: scope için kapalıysa 404 (config'lenebilir davranış). Yani on/off **request anında DB-driven**, provider yükle/kaldır değil.

Gerekçe: service provider'lar framework bootstrap'ta, DB sorgulanmadan önce boot olur. O yüzden "paketi yükle/yükleme" kararı DB'den yönetilemez; ama route/behavior request anında cache'li bir DB okumasıyla gate'lenebilir. Feature flag'ler alt-davranışları aynı şekilde gate'ler.

## 6. Veri modeli

Tek birleşik tablo (üç ayrı tablo yerine - tek resolution path + tek migration):

```
module_states
  id
  scope_type  (nullable)   -- null = global
  scope_id    (nullable)
  module      (slug)
  kind        enum(state|feature|setting)
  key         (nullable)   -- state: null; feature/setting: 'comments'/'posts_per_page'
  value       (json)
  timestamps
  unique(scope_type, scope_id, module, kind, key)
```

- `kind=state, key=null` → enabled flag
- `kind=feature, key='comments'` → feature override
- `kind=setting, key='posts_per_page'` → ayar override
- Global satırlar: scope_type/scope_id null

Tradeoff: tek tablo = tek çözümleme yolu + tek migration, ama `value` polymorphic (json). Üç tablo daha tipli olurdu ama üç kat kod. Yalınlık için birleşik.

## 7. API yüzeyi (HttpMode `api`)

REST uçları (manager HttpMode + auth middleware gate'li, core `ApiController` envelope):

```
GET   /modules                        registry + current scope için etkin durum
GET   /modules/{slug}                 tek modül (manifest + etkin değerler)
PATCH /modules/{slug}                 enabled set (global veya scope - header/param)
PATCH /modules/{slug}/features/{key}  feature set
PATCH /modules/{slug}/settings/{key}  setting set
```

Scope istekte header/param ile belirtilir (yoksa global). Headless modda aynı işlemler `Modules` facade/servisiyle. `api` kapalıysa (headless) route kaydı olmaz.

## 8. Hata yönetimi ve güvenlik

- **Registry otorite:** manifest'te olmayan modül/feature/setting set edilemez → 422/404.
- **Safe default:** DB satırı yok → manifest default → hard default. Cache fail-open.
- **Scope:** yoksa global; bilinmeyen scope → global'e düşer.
- **Setting tipi:** manifest'teki `type` ile validate edilir (int/bool/string/json).

## 9. Test stratejisi

- Çözümleme precedence matrisi: scope/global/manifest-default/hard-default her kombinasyon.
- `EnsureModuleEnabled` gate (açık/kapalı, scope'lu/global).
- HttpMode başına API (headless = route yok, api = envelope).
- Manifest toplama (registry tüm provider'lardan doğru topluyor mu).
- Cache invalidation (yazım sonrası etkin değer güncelleniyor mu).
- Pest + core `PackageTestCase`.

## 10. Milestone'lara bölme

Sistem büyük; üç milestone, her biri tek başına çalışır+test edilir, ayrı spec→plan alır:

- **M1 (core):** `ModuleManifest` + `ModuleRegistry` contract + `PackageServiceProvider.moduleManifest()` entegrasyonu; aile modülleri manifest declare eder. DB yok. → İlk somut plan bu.
- **M2 (manager pkg):** DB + `ModuleManager` çözümleme (state/feature/setting) + `ScopeResolver` + cache. Headless servisler + `Modules` facade.
- **M3 (manager pkg):** REST API + `EnsureModuleEnabled` middleware.

## 11. Açık kararlar / v2

- Modül **bağımlılık zorlaması** (Blog `dependsOn` interactions ama interactions kapalı) → M1'de manifest'te declare edilir, **zorlama/uyarı** v2.
- Filament UI → kapsam dışı; ileride manager üstüne ayrı `ui` modu.
- Scope başına toplu import/export ayar → v2.

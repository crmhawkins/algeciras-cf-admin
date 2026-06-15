<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('ALGECIRAS C.F.')
            ->brandLogo(secure_asset('img/club/escudo.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(secure_asset('favicon.ico'))
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#2196F3'),
                'gray' => Color::Slate,
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
            ])
            ->font('Inter')
            ->navigationGroups([
                NavigationGroup::make('Contenido de la web')->collapsible(),
                NavigationGroup::make('Atención al usuario')->collapsible(),
                NavigationGroup::make('Datos económicos')->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString($this->adminCustomCss())
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * CSS custom para que el panel admin imite el sistema del cliente:
     * - Header azul brillante full-width
     * - Sidebar blanco con item activo en azul
     * - Tabla de seleccion de sector estilo cliente
     *
     * Web exterior NO se ve afectada (este CSS solo carga en /admin).
     */
    protected function adminCustomCss(): string
    {
        return <<<HTML
<style>
/* ============================================================
   Paleta cliente (compralaentrada-like) — solo /admin
   ============================================================ */
:root {
    --acf-blue: #2196F3;
    --acf-blue-dark: #1976D2;
    --acf-blue-light: #E3F2FD;
    --acf-blue-hover: #BBDEFB;
    --acf-text: #1F1F1F;
    --acf-text-muted: #6B7280;
    --acf-border: #E5E7EB;
    /* Sidebar oscuro azul marino (estilo cliente) */
    --acf-navy: #16213A;
    --acf-navy-dark: #101829;
    --acf-navy-light: #233152;
    --acf-navy-text: #FFFFFF;
    --acf-navy-muted: #AEB9CF;
}

/* Topbar — barra azul brillante full-width, SIN logo (logo solo en sidebar) */
.fi-topbar {
    background: var(--acf-blue) !important;
    border-bottom: 0 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,.08) !important;
}
.fi-topbar nav { background: transparent !important; }
/* Ocultar el lockup duplicado del topbar — la imagen del cliente solo lo tiene en sidebar */
nav.fi-topbar .fi-logo,
nav.fi-topbar a.fi-logo,
nav.fi-topbar img.fi-logo,
.fi-topbar-start > a,
.fi-topbar-start .fi-logo {
    display: none !important;
}
.fi-topbar .fi-icon-btn,
.fi-topbar .fi-link,
.fi-topbar a {
    color: #fff !important;
}
.fi-topbar .fi-icon-btn:hover {
    background: rgba(255,255,255,.12) !important;
}
/* Buscador del topbar — input blanco con bordes redondeados */
.fi-topbar input[type="search"],
.fi-topbar .fi-input {
    background: rgba(255,255,255,.95) !important;
    border-radius: 8px !important;
    color: var(--acf-text) !important;
}

/* Sidebar OSCURO azul marino con escudo arriba (estilo cliente) */
.fi-sidebar {
    background: var(--acf-navy) !important;
    border-right: 0 !important;
}
.fi-sidebar-header {
    background: var(--acf-navy-dark) !important;
    border-bottom: 1px solid rgba(255,255,255,.08) !important;
    padding: 18px 16px !important;
    display: flex !important;
    justify-content: center !important;
}
.fi-sidebar-header .fi-logo {
    color: #fff !important;
    font-weight: 800 !important;
    letter-spacing: 1px !important;
    font-size: 14px !important;
    text-align: center !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    gap: 8px !important;
}
.fi-sidebar-header img {
    max-height: 64px !important;
    width: auto !important;
}

/* Sidebar nav items — texto claro sobre fondo oscuro */
.fi-sidebar-nav { background: transparent !important; }
.fi-sidebar-group-label,
.fi-sidebar-group-button {
    color: var(--acf-navy-muted) !important;
    font-weight: 600 !important;
    font-size: 11px !important;
    letter-spacing: .5px !important;
    text-transform: uppercase !important;
}
.fi-sidebar-item-button {
    color: var(--acf-navy-text) !important;
    border-radius: 0 !important;
    padding: 12px 16px !important;
    font-weight: 500 !important;
    border-left: 3px solid transparent !important;
    transition: background .15s, border-color .15s !important;
}
/* El label es un elemento aparte que Filament colorea oscuro -> forzar blanco */
.fi-sidebar-item-button .fi-sidebar-item-label,
.fi-sidebar-item-label {
    color: var(--acf-navy-text) !important;
    font-weight: 500 !important;
}
.fi-sidebar-item-button:hover {
    background: var(--acf-navy-light) !important;
}
.fi-sidebar-item-button:hover .fi-sidebar-item-label {
    color: #fff !important;
}
.fi-sidebar-item-button .fi-icon {
    color: var(--acf-navy-muted) !important;
}
.fi-sidebar-item-button:hover .fi-icon {
    color: #fff !important;
}
/* Item activo: fondo azul brillante + barra blanca a la izquierda */
.fi-sidebar-item-active > .fi-sidebar-item-button,
.fi-sidebar-item-button.fi-active {
    background: var(--acf-blue) !important;
    color: #fff !important;
    border-left-color: #fff !important;
    font-weight: 600 !important;
}
.fi-sidebar-item-active > .fi-sidebar-item-button .fi-icon,
.fi-sidebar-item-button.fi-active .fi-icon {
    color: #fff !important;
}
.fi-sidebar-item-active > .fi-sidebar-item-button .fi-sidebar-item-label,
.fi-sidebar-item-button.fi-active .fi-sidebar-item-label {
    color: #fff !important;
}
/* Labels de grupo (colapsables) también legibles */
.fi-sidebar-group-label,
.fi-sidebar-group-button,
.fi-sidebar-group-button .fi-sidebar-group-label {
    color: var(--acf-navy-muted) !important;
}
/* Boton colapsar sidebar y otros controles en claro */
.fi-sidebar .fi-icon-btn { color: var(--acf-navy-text) !important; }

/* ============================================================
   FIX selectores REALES de Filament v5:
   - item  = .fi-sidebar-item-btn   (NO .fi-sidebar-item-button)
   - activo = li.fi-sidebar-item.fi-active
   - grupo = .fi-sidebar-group-btn
   Sin esto, el item activo se quedaba con el fondo claro por
   defecto de Filament + texto blanco forzado = ILEGIBLE.
   ============================================================ */
.fi-sidebar-item-btn {
    color: var(--acf-navy-text) !important;
    border-radius: 0 !important;
    border-left: 3px solid transparent !important;
    padding: 12px 16px !important;
}
.fi-sidebar-item-btn .fi-sidebar-item-label { color: var(--acf-navy-text) !important; }
.fi-sidebar-item-btn .fi-icon { color: var(--acf-navy-muted) !important; }
.fi-sidebar-item-btn:hover { background: var(--acf-navy-light) !important; }
.fi-sidebar-item-btn:hover .fi-sidebar-item-label,
.fi-sidebar-item-btn:hover .fi-icon { color: #fff !important; }

/* ITEM ACTIVO: fondo azul brillante + barra blanca + texto blanco legible */
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
    background: var(--acf-blue) !important;
    border-left-color: #fff !important;
}
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label,
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-icon { color: #fff !important; }
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn:hover { background: var(--acf-blue-dark) !important; }

/* Labels de grupo */
.fi-sidebar-group-btn,
.fi-sidebar-group-btn .fi-sidebar-group-label { color: var(--acf-navy-muted) !important; }

/* Main container fondo gris claro como la imagen */
.fi-main {
    background: #F4F6F8 !important;
}

/* Page title color oscuro y peso bold */
.fi-page-header h1,
.fi-header-heading {
    color: var(--acf-text) !important;
    font-weight: 700 !important;
}

/* Botones primarios redondos y azules */
.fi-btn-color-primary {
    background: var(--acf-blue) !important;
    border-color: var(--acf-blue) !important;
}
.fi-btn-color-primary:hover {
    background: var(--acf-blue-dark) !important;
}

/* Cards / Section style cliente */
.fi-section {
    background: #fff !important;
    border: 1px solid var(--acf-border) !important;
    border-radius: 8px !important;
    box-shadow: 0 1px 2px rgba(0,0,0,.04) !important;
}

/* ============================================================
   Estilos especificos pagina VentaAbonos
   ============================================================ */
.va-action-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 28px;
    padding: 18px 20px;
    background: #fff;
    border: 1px solid var(--acf-border);
    border-radius: 8px;
}
.va-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #1F1F1F;
    color: #fff;
    border: 0;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: background .15s, transform .15s;
    text-decoration: none;
}
.va-action-btn:hover {
    background: #000;
    transform: translateY(-1px);
}
.va-action-btn .va-icon { font-size: 14px; }

.va-section-title {
    font-size: 28px;
    font-weight: 800;
    color: var(--acf-text);
    margin-bottom: 4px;
}
.va-section-desc {
    color: var(--acf-text-muted);
    margin-bottom: 18px;
    font-size: 14px;
}

.va-renovacion-card,
.va-altas-card {
    background: #fff;
    border: 1px solid var(--acf-border);
    border-radius: 8px;
    padding: 28px;
    margin-bottom: 28px;
}

.va-filtros-row {
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}
.va-filtros-label {
    font-weight: 700;
    color: var(--acf-text);
    font-size: 12px;
    letter-spacing: .5px;
    text-transform: uppercase;
}
.va-radio-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    color: var(--acf-text-muted);
    font-size: 14px;
}
.va-radio-pill input[type="radio"] {
    accent-color: var(--acf-blue);
    width: 18px;
    height: 18px;
}
.va-radio-pill input[type="radio"]:checked + .va-radio-label {
    color: var(--acf-text);
    font-weight: 600;
}

.va-search-input {
    width: 100%;
    border: 0;
    border-bottom: 1px solid #D1D5DB;
    padding: 12px 0;
    font-size: 16px;
    background: transparent;
    color: var(--acf-text);
    outline: none;
    transition: border-color .15s;
}
.va-search-input:focus {
    border-bottom-color: var(--acf-blue);
}

.va-submit-btn {
    margin-top: 20px;
    background: #000;
    color: #fff;
    border: 0;
    padding: 12px 32px;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-radius: 4px;
    cursor: pointer;
    transition: background .15s;
}
.va-submit-btn:hover {
    background: #2196F3;
}

.va-altas-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1100px) {
    .va-altas-grid { grid-template-columns: 1fr; }
}
.va-sectors-search {
    width: 100%;
    border: 0;
    border-bottom: 1px solid #D1D5DB;
    padding: 12px 0;
    font-size: 16px;
    background: transparent;
    outline: none;
    margin-bottom: 12px;
}
.va-sectors-search:focus { border-bottom-color: var(--acf-blue); }

.va-sectors-table {
    width: 100%;
    max-height: 360px;
    overflow-y: auto;
    border-top: 1px solid var(--acf-border);
}
.va-sectors-table table {
    width: 100%;
    border-collapse: collapse;
}
.va-sectors-table th {
    text-align: left;
    padding: 12px 8px;
    font-size: 11px;
    color: var(--acf-text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 1px solid var(--acf-border);
    background: #FAFBFC;
    position: sticky;
    top: 0;
}
.va-sectors-table td {
    padding: 14px 8px;
    border-bottom: 1px solid var(--acf-border);
    color: var(--acf-text);
    cursor: pointer;
    transition: background .12s;
}
.va-sectors-table tr:hover td {
    background: var(--acf-blue-light);
}
.va-sectors-table tr.va-active td {
    background: var(--acf-blue);
    color: #fff;
}

.va-stadium-plan {
    background: #FAFBFC;
    border: 1px solid var(--acf-border);
    border-radius: 8px;
    padding: 16px;
    min-height: 380px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.va-stadium-plan iframe {
    width: 100%;
    height: 420px;
    border: 0;
    border-radius: 4px;
}

/* Selector idioma flotante (esfera abajo izquierda como en la imagen) */
.va-lang-fab {
    position: fixed;
    bottom: 24px;
    left: 24px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--acf-blue);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(33,150,243,.4);
    z-index: 40;
    font-size: 18px;
    font-weight: 700;
}
</style>
HTML;
    }
}

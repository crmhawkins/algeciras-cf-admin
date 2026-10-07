<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // KILL SWITCH DE CORREO — TEMPORAL (no enviar emails a NADIE).
        // Activado mientras se migra la BD de abonados del club. Fuerza TODO
        // el correo al canal 'log' (se escribe en storage/logs/laravel.log,
        // NO se conecta a ningún SMTP ni se entrega a nadie). Cubre
        // CheckoutService, cualquier Mailable y cualquier emisor futuro.
        //
        // Para REACTIVAR el correo: poner MAIL_KILL_SWITCH=false en el entorno
        // (Coolify) y limpiar config. Por defecto está ON (true) por seguridad.
        // ─────────────────────────────────────────────────────────────────
        if (filter_var(env('MAIL_KILL_SWITCH', true), FILTER_VALIDATE_BOOLEAN)) {
            config([
                'mail.default'                 => 'log',
                'mail.mailers.smtp.transport'  => 'log', // neutraliza incluso ->mailer('smtp')
            ]);
        }

        // Detrás de Traefik (proxy SSL), Laravel no detecta automáticamente
        // que la request entra por HTTPS. Forzamos el scheme en producción
        // para que asset(), url(), @vite() generen URLs https://.
        if (config('app.env') === 'production' || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }
}

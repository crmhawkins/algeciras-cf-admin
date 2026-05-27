<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Cupones demo para el área socio.
 *
 * Idempotente: usa Coupon::updateOrCreate por `code`.
 */
class CouponsSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $in90  = $today->copy()->addDays(90);
        $endOfSeason = Carbon::create($today->year + ($today->month >= 7 ? 1 : 0), 6, 30);

        $coupons = [
            [
                'code'                  => 'ALGECF15',
                'title'                 => '15% en la tienda oficial',
                'description'           => 'Disfruta de un 15% de descuento en cualquier producto de la tienda oficial del Algeciras CF.',
                'type'                  => 'percent',
                'value'                 => 15,
                'target_tier'           => 'all',
                'max_uses_per_customer' => 1,
                'total_stock'           => null,
                'valid_from'            => $today,
                'valid_until'           => $in90,
                'active'                => true,
            ],
            [
                'code'                  => 'ABONADO30',
                'title'                 => '30% para abonados en equipación',
                'description'           => 'Descuento exclusivo del 30% para abonados en la equipación oficial 26/27.',
                'type'                  => 'percent',
                'value'                 => 30,
                'target_tier'           => 'abonado',
                'max_uses_per_customer' => 1,
                'total_stock'           => null,
                'valid_from'            => $today,
                'valid_until'           => $in90,
                'active'                => true,
            ],
            [
                'code'                  => 'VIP50',
                'title'                 => '50% socio VIP en palco merch',
                'description'           => 'Privilegio exclusivo para abonados de Palco de Honor: 50% de descuento en merchandising premium.',
                'type'                  => 'percent',
                'value'                 => 50,
                'target_tier'           => 'abonado_vip',
                'max_uses_per_customer' => 1,
                'total_stock'           => null,
                'valid_from'            => $today,
                'valid_until'           => $endOfSeason,
                'active'                => true,
            ],
            [
                'code'                  => 'BUFANDA-FREE',
                'title'                 => 'Bufanda gratis temporada 26/27',
                'description'           => 'Recoge gratis tu bufanda oficial de la temporada 26/27 en la tienda del estadio.',
                'type'                  => 'gift',
                'value'                 => 0,
                'target_tier'           => 'abonado',
                'max_uses_per_customer' => 1,
                'total_stock'           => 500,
                'valid_from'            => $today,
                'valid_until'           => $endOfSeason,
                'active'                => true,
            ],
            [
                'code'                  => 'ENVIOGRATIS',
                'title'                 => 'Envío gratis tienda online',
                'description'           => 'Envío gratuito en tu próxima compra en la tienda online del club.',
                'type'                  => 'fixed',
                'value'                 => 0,
                'target_tier'           => 'all',
                'max_uses_per_customer' => 1,
                'total_stock'           => null,
                'valid_from'            => $today,
                'valid_until'           => $in90,
                'active'                => true,
            ],
            [
                'code'                  => 'PEÑA20',
                'title'                 => '20% para miembros de peñas registradas',
                'description'           => 'Descuento del 20% para miembros de peñas oficialmente registradas en el club.',
                'type'                  => 'percent',
                'value'                 => 20,
                'target_tier'           => 'peñista',
                'max_uses_per_customer' => 2,
                'total_stock'           => null,
                'valid_from'            => $today,
                'valid_until'           => $endOfSeason,
                'active'                => true,
            ],
        ];

        foreach ($coupons as $data) {
            Coupon::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command?->info('CouponsSeeder: '.count($coupons).' cupones creados/actualizados.');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use Illuminate\Database\Seeder;

/**
 * Patrocinadores REALES del Algeciras CF.
 *
 * Empresas verificadas en notas de prensa públicas del club (algecirasclubdefutbol.com),
 * web oficial vigente y medios locales (Deportes al Minuto, 8Directo, Diario Área,
 * Andalucía Información, Iniciativa Comarcal). No se inventa ningún patrocinador.
 *
 * Niveles válidos según migración:
 *   tecnico | principal | main | secundario | partner | colaborador
 *
 * Idempotente: usa updateOrCreate por slug. Convive con SponsorsSeeder original.
 */
class SponsorsRealSeeder extends Seeder
{
    public function run(): void
    {
        $sponsors = [
            // === MARCA TÉCNICA ===
            [
                'name'           => 'Capelli Sport',
                'slug'           => 'capelli-sport',
                'tier'           => 'tecnico',
                'logo'           => 'img/sponsors/capelli.png',
                'url'            => 'https://capellisport.com',
                'invert_on_dark' => false,
                'description'    => ['es' => 'Marca técnica oficial del Algeciras CF hasta 2030. Multinacional con sede en Nueva York, equipa al primer equipo, cantera y cuerpo técnico.'],
            ],

            // === PATROCINADORES PRINCIPALES ===
            [
                'name'           => 'Hawkins',
                'slug'           => 'hawkins',
                'tier'           => 'principal',
                'logo'           => 'img/sponsors/hawkins.png',
                'url'            => 'https://hawkins.es',
                'invert_on_dark' => true,
                'description'    => ['es' => 'Agencia algecireña de publicidad, marketing digital y desarrollo tecnológico. Premiada en los premios Agripina y socio digital del club.'],
            ],
            [
                'name'           => 'Quirónsalud',
                'slug'           => 'quironsalud',
                'tier'           => 'main',
                'logo'           => 'img/sponsors/quironsalud.svg',
                'url'            => 'https://www.quironsalud.com',
                'invert_on_dark' => false,
                'description'    => ['es' => 'Servicios médicos y de salud del primer equipo. Hospital de referencia para reconocimientos médicos y atención al deportista.'],
            ],
            [
                'name'           => 'Centro Gráfico',
                'slug'           => 'centro-grafico',
                'tier'           => 'main',
                'logo'           => 'img/sponsors/centro-grafico.png',
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Imprenta y artes gráficas de Algeciras, colaborador histórico del club en material impreso y merchandising.'],
            ],
            [
                'name'           => 'EWYT',
                'slug'           => 'ewyt',
                'tier'           => 'main',
                'logo'           => 'img/sponsors/ewyt.png',
                'url'            => 'https://ewyt.es',
                'invert_on_dark' => true,
                'description'    => ['es' => 'Partner principal de la temporada 2025/26 visible en la web oficial del club.'],
            ],

            // === PATROCINADORES Y PARTNERS DEL SECTOR PORTUARIO Y TRANSPORTE ===
            [
                'name'           => 'Coordinadora TPA',
                'slug'           => 'coordinadora-tpa',
                'tier'           => 'principal',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Coordinadora de Trabajadores del Puerto de Algeciras. Acuerdo de colaboración por dos temporadas con descuentos para afiliados que se hagan abonados.'],
            ],
            [
                'name'           => 'Scamp Marine',
                'slug'           => 'scamp-marine',
                'tier'           => 'principal',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa del sector portuario del Estrecho. Patrocinador del Algeciras CF.'],
            ],
            [
                'name'           => 'Surmeyca',
                'slug'           => 'surmeyca',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa del Campo de Gibraltar que se sumó al proyecto deportivo como patrocinador.'],
            ],
            [
                'name'           => 'Nuctech',
                'slug'           => 'nuctech',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => 'https://www.nuctech.com',
                'invert_on_dark' => false,
                'description'    => ['es' => 'Compañía tecnológica de soluciones de seguridad e inspección. Socio estratégico del club.'],
            ],
            [
                'name'           => 'Ematra SL',
                'slug'           => 'ematra-sl',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa local de transporte de carga. Renovó su acuerdo con el club una temporada más.'],
            ],
            [
                'name'           => 'Permotor',
                'slug'           => 'permotor',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Concesionario decano del Campo de Gibraltar. Cumple su vigésima tercera temporada colaborando con el Algeciras CF.'],
            ],
            [
                'name'           => 'Eurogruas',
                'slug'           => 'eurogruas',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Compañía especializada en grúas y elevación con presencia en el Estrecho. Acuerdo de patrocinio con el primer equipo.'],
            ],
            [
                'name'           => 'Kube Algeciras',
                'slug'           => 'kube-algeciras',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa local que renovó su patrocinio con el club para una nueva temporada.'],
            ],

            // === OTROS PATROCINADORES Y COLABORADORES ===
            [
                'name'           => 'Grupo Trocadero',
                'slug'           => 'grupo-trocadero',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => 'https://www.grupotrocadero.com',
                'invert_on_dark' => false,
                'description'    => ['es' => 'Grupo de restauración del sur de España. Acuerdo de patrocinio especial con el club.'],
            ],
            [
                'name'           => 'Neumalgex',
                'slug'           => 'neumalgex',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa de neumáticos y servicios para automoción del Campo de Gibraltar.'],
            ],
            [
                'name'           => 'Obramat',
                'slug'           => 'obramat',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => 'https://www.obramat.es',
                'invert_on_dark' => false,
                'description'    => ['es' => 'Distribuidor nacional de materiales de construcción y reformas. Partner del club.'],
            ],
            [
                'name'           => 'Eurograss',
                'slug'           => 'eurograss',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa especialista en césped artificial e instalaciones deportivas.'],
            ],
            [
                'name'           => 'Bezarala',
                'slug'           => 'bezarala',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Empresa colaboradora del Algeciras CF.'],
            ],
            [
                'name'           => 'Omoda/Jaecoo',
                'slug'           => 'omoda-jaecoo',
                'tier'           => 'secundario',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Marcas de automoción del grupo Chery presentes como patrocinadores.'],
            ],
            [
                'name'           => 'Gigantes Empresarios',
                'slug'           => 'gigantes-empresarios',
                'tier'           => 'colaborador',
                'logo'           => null,
                'url'            => null,
                'invert_on_dark' => false,
                'description'    => ['es' => 'Comunidad empresarial colaboradora del club.'],
            ],
        ];

        foreach ($sponsors as $i => $s) {
            $s['sort_order'] = $i;
            $s['active']     = true;
            Sponsor::updateOrCreate(['slug' => $s['slug']], $s);
        }
    }
}

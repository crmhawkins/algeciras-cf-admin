<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Noticias REALES del Algeciras CF (temporada 2025/26).
 *
 * Hechos verificados en notas de prensa públicas del club, web oficial
 * (algecirasclubdefutbol.com) y medios locales (Deportes al Minuto, 8Directo,
 * Diario Área, Onda Algeciras TV, RFEF). El cuerpo (body) y los resúmenes
 * (excerpt) son redactados de nuevo: no se reproducen frases textuales de
 * los medios. Cualquier dato verificable (fechas, contratos, nombres,
 * resultados) procede de información pública del club.
 *
 * Idempotente: usa updateOrCreate por slug.
 */
class NewsRealSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = optional(User::first())->id;

        $items = [
            [
                'slug'         => 'capelli-sport-marca-tecnica-hasta-2030',
                'title'        => 'Capelli Sport vestirá al Algeciras CF hasta 2030',
                'excerpt'      => 'El acuerdo plurianual con la multinacional con sede en Nueva York convierte a la marca en proveedor oficial y exclusivo del primer equipo, la cantera y el cuerpo técnico durante cinco temporadas.',
                'body'         => "El Algeciras CF y Capelli Sport firmaron un acuerdo plurianual que convierte a la marca neoyorquina en proveedor técnico oficial del club hasta 2030. El contrato cubre al primer equipo, a toda la cantera y al cuerpo técnico, incluyendo equipaciones de partido, material de entrenamiento y línea de paseo.\n\nCapelli Sport es una marca global multideporte presente en clubes y selecciones de varios continentes. Para el Algeciras CF supone estabilidad técnica a largo plazo y la posibilidad de desarrollar diseños personalizados temporada a temporada.\n\nLa temporada 2025/26 ya estrena la nueva línea, con una primera equipación que recupera la rojiblanca de rayas verticales clásica y una segunda equipación en tonos oscuros pensada para los partidos como visitante.",
                'category'     => 'equipacion',
                'cover_image'  => null,
                'featured'     => true,
                'published_at' => Carbon::create(2025, 6, 25, 12, 0),
            ],
            [
                'slug'         => 'una-vida-contigo-campana-abonados-2025-2026',
                'title'        => '"Una vida contigo": el club presenta la campaña de abonados 2025/26',
                'excerpt'      => 'Capitaneada por Iván Turrillo, la campaña de la temporada apela al vínculo emocional entre el club y su afición. Renovaciones desde 90 euros y nuevas altas a partir del 1 de julio.',
                'body'         => "El Algeciras CF presentó el 6 de junio su campaña de abonados para la temporada 2025/26 bajo el lema \"Una vida contigo\", con el capitán Iván Turrillo como protagonista. La campaña pone el foco en el vínculo generacional entre el club y su afición y en la fidelidad de quienes acompañan al equipo en cada jornada.\n\nLos precios de renovación se sitúan en 190 euros para Tribuna, 120 en Preferencia y 90 en Fondos. Para nuevos abonados las tarifas son de 225, 140 y 115 euros respectivamente. Existen tarifas reducidas para niños de 4 a 14 años y precios específicos para los palcos.\n\nLas renovaciones online comenzaron tras la presentación, con la reserva de asiento garantizada hasta el 30 de junio. La renovación presencial se inició el 11 de junio y la venta a nuevos abonados quedó abierta a partir del 1 de julio.",
                'category'     => 'actualidad',
                'cover_image'  => null,
                'featured'     => true,
                'published_at' => Carbon::create(2025, 6, 6, 18, 0),
            ],
            [
                'slug'         => 'javi-vazquez-nuevo-entrenador-primer-equipo',
                'title'        => 'Javi Vázquez, nuevo entrenador del primer equipo',
                'excerpt'      => 'El técnico madrileño llega procedente del CF Talavera y firma para dirigir al Algeciras CF en la temporada 2025/26 de Primera Federación.',
                'body'         => "El Algeciras CF anunció la incorporación de Javi Vázquez como nuevo entrenador del primer equipo. El técnico madrileño, nacido en 1986, llega procedente del CF Talavera y firma con el club algecirista de cara a la temporada 2025/26.\n\nVázquez aterriza en el banquillo del Nuevo Mirador con la misión de consolidar al equipo en la categoría y dar continuidad a un proyecto deportivo orientado a competir en la zona alta del grupo II de Primera Federación. El club hizo oficial el acuerdo a través de sus canales y avanzó que el entrenador sería presentado en los días posteriores.",
                'category'     => 'plantilla',
                'cover_image'  => null,
                'featured'     => true,
                'published_at' => Carbon::create(2025, 6, 13, 11, 0),
            ],
            [
                'slug'         => 'coordinadora-tpa-acuerdo-patrocinio',
                'title'        => 'Acuerdo de colaboración entre el Algeciras CF y la Coordinadora TPA',
                'excerpt'      => 'El club y la Coordinadora de Trabajadores del Puerto firman un convenio por dos temporadas que incluye un descuento del 10% en abonos para afiliados.',
                'body'         => "El Algeciras CF y la Coordinadora TPA, que agrupa a los trabajadores del puerto de Algeciras, formalizaron un acuerdo de colaboración por dos temporadas. La alianza refuerza el vínculo histórico entre el club rojiblanco y el sector portuario, eje económico de la ciudad.\n\nEl acuerdo contempla, entre otras medidas, un descuento del 10% para los afiliados a la Coordinadora que decidan hacerse abonados del club. Es una de las acciones concretas pensadas para acercar todavía más el equipo a una de las comunidades laborales más numerosas del Campo de Gibraltar.",
                'category'     => 'patrocinios',
                'cover_image'  => null,
                'featured'     => false,
                'published_at' => Carbon::create(2025, 9, 18, 12, 30),
            ],
            [
                'slug'         => 'permotor-renueva-23-temporada-patrocinio',
                'title'        => 'Permotor renueva su patrocinio por vigésimo tercera temporada',
                'excerpt'      => 'El concesionario decano del Campo de Gibraltar continúa de la mano del club tras 23 años consecutivos de patrocinio ininterrumpido.',
                'body'         => "Permotor renovó su acuerdo de patrocinio con el Algeciras CF para una nueva temporada, prolongando una relación que arrancó hace ya veintitrés años. El concesionario, referente histórico de la automoción en el Campo de Gibraltar, mantiene así su apuesta por el primer equipo del fútbol algecireño.\n\nLa renovación pone en valor uno de los acuerdos de patrocinio más longevos del club y refuerza la red de empresas locales que sostienen el proyecto deportivo temporada tras temporada.",
                'category'     => 'patrocinios',
                'cover_image'  => null,
                'featured'     => false,
                'published_at' => Carbon::create(2025, 8, 20, 10, 0),
            ],
            [
                'slug'         => 'ematra-renueva-patrocinio-temporada',
                'title'        => 'Ematra SL continúa una temporada más con el Algeciras CF',
                'excerpt'      => 'La empresa local de transporte de carga renueva su acuerdo con el club rojiblanco y refuerza el bloque de patrocinadores del sector logístico.',
                'body'         => "Ematra SL, empresa especializada en transporte de carga con sede en el Campo de Gibraltar, renovó su acuerdo de colaboración con el Algeciras CF. La compañía suma otra temporada apoyando al club y consolida su presencia en el panel de patrocinadores rojiblancos.\n\nLa renovación encaja con la estrategia del club de tejer alianzas duraderas con empresas del tejido económico local, especialmente del sector logístico y portuario.",
                'category'     => 'patrocinios',
                'cover_image'  => null,
                'featured'     => false,
                'published_at' => Carbon::create(2025, 9, 5, 12, 0),
            ],
            [
                'slug'         => 'nuctech-acuerdo-patrocinio',
                'title'        => 'El Algeciras CF y Nuctech unen sus caminos',
                'excerpt'      => 'La compañía tecnológica de soluciones de seguridad e inspección se incorpora al proyecto deportivo como socio estratégico.',
                'body'         => "El Algeciras CF y Nuctech alcanzaron un acuerdo de patrocinio que incorpora a la compañía tecnológica al grupo de socios estratégicos del club. Nuctech es una empresa internacional especializada en soluciones de seguridad e inspección, con presencia en infraestructuras críticas como puertos y aeropuertos.\n\nEl acuerdo refuerza la apuesta del club por sumar al proyecto a compañías punteras de sectores tecnológicos y de innovación, complementando a los patrocinadores tradicionales del entorno industrial y portuario.",
                'category'     => 'patrocinios',
                'cover_image'  => null,
                'featured'     => false,
                'published_at' => Carbon::create(2025, 10, 2, 11, 0),
            ],
            [
                'slug'         => 'eurogruas-nuevo-patrocinio',
                'title'        => 'Acuerdo de patrocinio entre Eurogruas y el Algeciras CF',
                'excerpt'      => 'La compañía de grúas y servicios de elevación pasa a formar parte del bloque de patrocinadores del primer equipo.',
                'body'         => "Eurogruas, empresa especializada en grúas, elevación y trabajos en altura con presencia destacada en el Estrecho, firmó un acuerdo de patrocinio con el Algeciras CF. La compañía se suma así al grupo de empresas que apoyan al primer equipo y refuerza la presencia del sector industrial en el panel de socios del club.\n\nLa colaboración se enmarca en la línea estratégica del Algeciras CF de tejer una red sólida con el tejido empresarial del Campo de Gibraltar.",
                'category'     => 'patrocinios',
                'cover_image'  => null,
                'featured'     => false,
                'published_at' => Carbon::create(2025, 9, 12, 13, 0),
            ],
            [
                'slug'         => 'algeciras-goleada-europa-jornada-30',
                'title'        => 'Goleada del Algeciras CF en el campo del CE Europa (0-3)',
                'excerpt'      => 'Víctor Ruiz, Manín y Óscar Castro firmaron los goles en una victoria de prestigio del equipo rojiblanco en la jornada 30 de Primera Federación.',
                'body'         => "El Algeciras CF se impuso por 0-3 al CE Europa a domicilio en la jornada 30 de la temporada 2025/26 de Primera Federación. Los goles de la victoria los firmaron Víctor Ruiz en el minuto 62, Manín en el 84 y Óscar Castro en el descuento (90+5).\n\nFue una de las exhibiciones más completas del equipo en las últimas semanas, con un dominio claro en la segunda mitad y una eficacia de cara a puerta que permitió cerrar el partido con una goleada de prestigio fuera de casa.",
                'category'     => 'actualidad',
                'cover_image'  => null,
                'featured'     => true,
                'published_at' => Carbon::create(2026, 4, 13, 21, 0),
            ],
            [
                'slug'         => 'hawkins-agencia-publicidad-digital',
                'title'        => 'Hawkins, nueva agencia de publicidad y marketing digital del club',
                'excerpt'      => 'La agencia algecireña, premiada en los Agripina, asume la dirección creativa, publicitaria y digital del Algeciras CF.',
                'body'         => "El Algeciras CF anunció a Hawkins como nueva agencia de publicidad y marketing digital del club. La compañía, con sede en la propia ciudad de Algeciras, se encarga del trabajo creativo, publicitario y digital del equipo, incluyendo campañas, identidad gráfica y proyectos audiovisuales.\n\nHawkins es una agencia reconocida por su apuesta por la tecnología, el diseño y la realidad virtual, y ha sido premiada en los premios Agripina en categorías como Realidad Aumentada y Aplicaciones con un proyecto de Metaverso. Su incorporación refuerza la línea de modernización digital que está aplicando el club en su web, su tienda online y su comunicación con la afición.",
                'category'     => 'patrocinios',
                'cover_image'  => null,
                'featured'     => false,
                'published_at' => Carbon::create(2025, 7, 22, 12, 0),
            ],
        ];

        foreach ($items as $item) {
            // El modelo News usa HasTranslations: title/excerpt/body se guardan como JSON con clave de idioma.
            $payload = [
                'title'        => ['es' => $item['title']],
                'excerpt'      => ['es' => $item['excerpt']],
                'body'         => ['es' => $item['body']],
                'category'     => $item['category'],
                'cover_image'  => $item['cover_image'],
                'featured'     => $item['featured'],
                'published_at' => $item['published_at'],
                'author_id'    => $authorId,
            ];

            News::updateOrCreate(['slug' => $item['slug']], $payload);
        }
    }
}

# Réplica del CRM proveedor (compralaentrada) — Abonados + Eventos

Inventario read-only del panel `algeciras.compralaentrada.com/admin` para replicar
EXACTO en nuestro Filament. **NO se modifica nada en el proveedor. NO se envía nada.**

Rutas internas del proveedor: `/admin/abonados/`, `/admin/eventos/`,
`/admin/abonos/` (venta), `/admin/envios/`, `/admin/acreditaciones/`,
`/admin/mensajes/`, `/admin/1014/deportistas/`. (1014 = id de la temporada/lista.)

---

## 1. ABONADOS  (`/admin/abonados/`)

### Listado
- Selector de **temporada** (2026/2027…).
- **Buscar** + **Buscar por** (Nº Abonado / DNI / Apellidos / …).
- Filtros: **Tipo de abono**, **Método de pago**, **Tipo de abonado** (Normal/…),
  **Estado**. Botón **Limpiar filtros**.
- Columnas: **Estado** (punto rojo=inactivo/no renovado, verde=activo) · **Nº de
  abonado** · **Origen** (Desconocido / Renovación online / Renovación en taquilla)
  · **DNI** · **Nombre** · **Email** · **Acciones**.
- Botones arriba-dcha: **Añadir nuevo abonado** · **Opciones** (menú:
  *Enviar abonos* [email masivo] / *Imprimir* / *Descargar*).
- Paginación + "elementos por página".

### Acciones por fila (botón OPCIONES) — varían según abonado
Ver abonado · Ver usuario · Enviar abono [EMAIL] · Descargar abono · Editar abonado
· Imprimir abono · Imprimir recibo · Reimpresión perdida · Sanciones · Liberar
asiento · Realizar devolución · Borrar abonado.

### Ficha (Ver abonado) — `/admin/abonados/{lista}/{id}`
- **Datos personales**: nombre completo, Nº abonado, Nº antiguo, género, teléfono,
  móvil, email, fecha nacimiento, DNI, domicilio (dirección/CP/localidad/provincia/
  país), datos laborales, **peña/empresa**.
- **Datos del abono**: Asiento (ZONA, Fila, Butaca) + botón **Cambiar** · Tipo de
  abono · Precio · botón **Cambiar tipo de abono y precio** · Fecha de renovación ·
  Fecha de antigüedad · Observaciones · IBAN · Titular cuenta · **Vendido por** ·
  Origen · Código · **QR code** · Impresión · Envío a domicilio.

### PENDIENTE de capturar al construir cada pieza
- Campos exactos del form "Añadir / Editar abonado".
- Opciones de cada dropdown de filtro.
- Pantallas de Sanciones / Liberar asiento / Realizar devolución.

---

## 2. EVENTOS  (`/admin/eventos/`)

### Listado
- Tabs: **Eventos actuales** / **Eventos pasados** / **Resumen de ventas**.
- **Buscar**. Botones: **Packs de eventos** · **Crear evento**.
- Fila de evento: id · estado (✓) · ubicación (📍) · nombre
  (`ALGECIRAS CF - RIVAL (JORNADA N)`).
- Botones por evento: **Compañía** · **Informe** · **Sesiones** · **Opciones**
  (menú: *Editar* / *Borrar* / *Acreditaciones* / *Campos personalizados*).

### PENDIENTE de capturar al construir cada pieza
- Form "Crear evento" (campos: rival, jornada, fecha, ubicación, mapa de zonas/
  precios, sesiones…).
- Contenido de Compañía / Informe (ventas) / Sesiones / Packs de eventos / Resumen
  de ventas / Acreditaciones / Campos personalizados.

---

## 3. Qué tenemos YA en nuestro sistema (reutilizable)
- `Customer` (3337 abonados importados, socio_number) · `Ticket` (abono/entrada con
  QR + legacy_qr) · `FootballMatch` · `Sector`/`Seat` (mapa estadio real) · productos
  abono · checkout Redsys · validador QR puerta · carnet PVC · página Venta de Abonos.
- Falta el "envoltorio" tipo proveedor: listado Abonados con sus acciones, y el
  módulo Eventos (crear evento → sesiones → ventas → informes → packs).

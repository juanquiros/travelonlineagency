# Travel Online Agency

Travel Online Agency es una plataforma web diseñada para gestionar servicios de reservas de alojamientos (Booking) y traslados, ofreciendo herramientas robustas para administradores, choferes y clientes. El administrador puede registrar y gestionar servicios, asignar viajes a choferes, procesar reservas, descargar reportes en diversos formatos y configurar parámetros clave como comisiones, tiempos de cancelación y métodos de pago (MercadoPago y PayPal). Los choferes acceden a un panel para aceptar o rechazar viajes, gestionar penalizaciones y visualizar su historial de actividades. Por su parte, los clientes pueden explorar servicios disponibles, realizar solicitudes de traslados o reservas, pagar mediante plataformas habilitadas y rastrear el estado de sus solicitudes. La interfaz incluye formularios de contacto, opciones de personalización para los servicios y un panel administrativo con métricas clave para un control eficiente de la operación.

## Requisitos del entorno

- PHP **8.2** o superior (la aplicación y sus dependencias están fijadas para esa versión).
- Composer 2.6 o superior.
- Extensiones de PHP: `curl`, `intl`, `zip`, `pdo_pgsql`, `gd`.
- Node.js 18+ y npm (para compilar assets administrados con Symfony Asset Mapper).
- Docker y Docker Compose (opcional, pero recomendados para los servicios auxiliares).

> ⚠️ Si ejecutás `composer install` o `composer update` con PHP 8.1 obtendrás un error similar a:
>
> ```
> Root composer.json requires php >=8.2 but your php version (8.1.x) does not satisfy that requirement.
> ```
>
> Asegurate de usar PHP 8.2 localmente (por ejemplo con [Symfony CLI](https://symfony.com/download) o administradores de versiones como `asdf`/`phpenv`). Como alternativa rápida podés apoyarte en la imagen oficial de Composer que ya incluye PHP 8.2:
>
> ```bash
> docker run --rm -it -v "$(pwd)":/app -w /app composer:2 bash -lc "composer install"
> ```

## Puesta en marcha

1. Copiá el archivo `.env` a `.env.local` y actualizá las credenciales necesarias.
2. Instalá dependencias PHP:
   ```bash
   composer install
   ```
3. Arrancá los servicios auxiliares (base de datos PostgreSQL y Mailpit) si lo necesitás:
   ```bash
   docker compose up -d database mailer
   ```
4. Ejecutá las migraciones y carga de datos iniciales:
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate
   ```
5. Iniciá el servidor de desarrollo:
   ```bash
   symfony server:start
   ```
6. Compilá los assets front-end en modo watch:
   ```bash
   npm install
   npm run dev -- --watch
   ```

## Datos de prueba para phpMyAdmin

- El archivo [`data/phpmyadmin_seed.sql`](data/phpmyadmin_seed.sql) ahora carga un set amplio para **todas** las entidades del dominio: usuarios, partners, servicios, precios, traducciones, estados de reserva, solicitudes, pagos (Mercado Pago y PayPal), preguntas frecuentes, mensajes de contacto y suscripciones push.
- Importalo desde phpMyAdmin (o `mysql` CLI) luego de crear la base de datos y ejecutar las migraciones:
  ```sql
  SOURCE data/phpmyadmin_seed.sql;
  ```
- El script deshabilita temporalmente los `FOREIGN_KEY_CHECKS`, trunca tablas clave (`usuario`, `booking_partner`, `booking`, `precio`, `solicitud_reserva`, `mercado_pago_pago`, etc.) y vuelve a activarlos al final para asegurar la integridad referencial.
- Encontrarás ejemplos concretos para:
  - Partners habilitados y pendientes (con cuentas vinculadas a Mercado Pago en modo split).
  - Reservas en distintos estados (pendiente, confirmada, cancelada) con pagos asociados en Mercado Pago y PayPal.
  - Traslados configurados (destinos, combos, campos dinámicos) con solicitudes activas, asignaciones de chofer y pagos de prueba.
  - Traducciones de plataforma, servicios y preguntas frecuentes en español, inglés y portugués.
  - Mensajes de contacto respondidos desde la administración y suscripciones a notificaciones push.
- Las credenciales de acceso principales son:
  - **Administrador** → `admin@travelonlineagency.test` / `Password123`
  - **Partner habilitado** → `sofia.partner@test.com` / `Password123`
  - **Partner pendiente** → `martin.partner@test.com` / `Password123`
  - **Chofer habilitado** → `driver@travelonlineagency.test` / `Password123`

## Flujo de ramas

- `main`: rama estable para despliegues en producción.
- `main-codex`: rama de trabajo para la evolución del módulo de partners y pruebas asociadas. Todas las nuevas iteraciones sobre este flujo deben abrirse desde aquí y luego fusionarse a `main` cuando se validen.

## Gestión de partners de reservas

El administrador puede habilitar o suspender partners desde **Administrador → Partners**. Cada partner debe enviar una solicitud (desde el registro marcando “Quiero ofrecer servicios como partner”) y queda pendiente hasta que el administrador lo apruebe y, opcionalmente, defina la comisión de la plataforma. Una vez aprobado y con el rol `ROLE_PARTNER`, el partner accede al panel `/booking-partner` para cargar y editar servicios propios utilizando el formulario reutilizado de reservas.

Los partners que todavía no están habilitados o a quienes les falta el rol reciben un mensaje contextual indicando el paso pendiente para completar su activación.

- Desde la sección de **Preguntas Frecuentes** del panel de administrador se expone un enlace privado de invitación (`/register/partner/{code}`) que podés copiar y pegar en una pregunta para compartir el registro exclusivo con proveedores externos. Solo quienes accedan mediante ese enlace podrán enviar la solicitud de partner.
- Los partners y administradores pueden habilitar notificaciones push (botón “Activar notificaciones”) para recibir avisos cuando se confirmen reservas vinculadas a sus servicios.

## Gestión de traslados y choferes

- El administrador dispone de un nuevo módulo en **Administrador → Traslados** para crear destinos individuales, armar combos preestablecidos, definir campos dinámicos (número de vuelo, cantidad de pasajeros, etc.) y monitorear todas las solicitudes. Desde el listado puede asignar un viaje a un chofer aprobado o cambiar manualmente su estado.
- Los formularios públicos de `/traslados` permiten solicitar un combo o armar un itinerario personalizado. Cada solicitud genera un token de seguimiento, un PDF con QR descargable y un email automático para el pasajero con el enlace directo al tracking.
- Los choferes se registran desde un enlace privado (`/register/driver/{code}`) que el administrador puede publicar en las preguntas frecuentes. Al aprobarlos en **Administrador → Choferes**, reciben el rol `ROLE_DRIVER` y acceden al panel `/chofer` donde pueden capturar viajes, avanzar paradas, agregar notas, cancelar o finalizar un traslado.
- El panel de chofer muestra las paradas completadas, los datos del pasajero y el acceso al seguimiento público para coordinar con el turista o el administrador. Todas las acciones están protegidas con tokens CSRF.
- Tanto el resumen como el tracking de cada traslado ofrecen los botones para pagar con Mercado Pago o PayPal reutilizando las credenciales configuradas en la plataforma. En entornos de desarrollo se aplica automáticamente el modo sandbox de Mercado Pago.

## Pagos con Mercado Pago y split de comisiones

- **Administrador**: debe completar el `client_id`, `client_secret`, `public_key` y `access_token` de la aplicación en **Administrador → Configuraciones → Mercado Pago**. Una vez cargados, desde la nueva pestaña **Balance** puede vincular su propia cuenta (OAuth), consultar el saldo disponible/pending, ver las últimas operaciones y desconectar el acceso si fuera necesario.
- **Partners**: cada partner habilitado encuentra la pestaña **Balance** en `/booking-partner/balance`. Desde allí puede generar el enlace de vinculación, autorizar a la plataforma para operar en su nombre y revisar sus saldos (disponible, pendiente y total). También dispone de la opción para revocar la conexión.
- **Flujo de cobro**: cuando un servicio pertenece a un partner con cuenta conectada, la preferencia de pago se genera con el `access_token` del partner y la plataforma cobra automáticamente su comisión vía `application_fee`. Si el partner no está vinculado, el cobro se realiza con las credenciales de la plataforma como hasta ahora.
- **Modo sandbox en desarrollo**: si la aplicación se ejecuta con `APP_ENV=dev`, el checkout fuerza el modo Sandbox de Mercado Pago. Esto permite probar el flujo completo con credenciales de test (public/ access token) sin impactar cuentas reales; asegurate de cargar las claves de prueba correspondientes en la configuración de la plataforma o del partner.
- **Migraciones requeridas**: `php bin/console doctrine:migrations:migrate` agrega los campos `nickname`/`email` a `credenciales_mercado_pago`, crea la relación entre partners y sus credenciales de Mercado Pago, y almacena el `application_fee` de cada pago para el panel del administrador.

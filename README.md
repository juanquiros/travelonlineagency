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

## Gestión de partners de reservas

El administrador puede habilitar o suspender partners desde **Administrador → Partners**. Cada partner debe enviar una solicitud (desde el registro marcando “Quiero ofrecer servicios como partner”) y queda pendiente hasta que el administrador lo apruebe y, opcionalmente, defina la comisión de la plataforma. Una vez aprobado y con el rol `ROLE_PARTNER`, el partner accede al panel `/booking-partner` para cargar y editar servicios propios utilizando el formulario reutilizado de reservas.

Los partners que todavía no están habilitados o a quienes les falta el rol reciben un mensaje contextual indicando el paso pendiente para completar su activación.

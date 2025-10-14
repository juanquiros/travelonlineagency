SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Limpieza de tablas principales
TRUNCATE TABLE precio;
TRUNCATE TABLE booking;
TRUNCATE TABLE booking_partner;
TRUNCATE TABLE usuario;
TRUNCATE TABLE lenguaje;
TRUNCATE TABLE plataforma;
TRUNCATE TABLE moneda;

-- Monedas base para los precios y lenguajes
INSERT INTO moneda (id, nombre, simbolo, habilitada) VALUES
  (1, 'Dólar estadounidense', 'USD', 1),
  (2, 'Peso argentino', 'ARS', 1);

-- Plataforma general de la agencia
INSERT INTO plataforma (
  id,
  nombre,
  traslados_od_libres,
  tasa_traslados_def,
  language_def_id,
  moneda_def_id,
  credenciales_pay_pal_id,
  credenciales_mercado_pago_id,
  logo,
  icono,
  link_instagram,
  link_whatsapp,
  contacto_telefono,
  contacto_correo,
  contacto_direccion,
  comision_booking_partner
) VALUES
  (1,
   'Travel Online Agency',
   0,
   15.0,
   1,
   1,
   NULL,
   NULL,
   'logo-toa.png',
   'favicon-toa.png',
   'https://www.instagram.com/travelonlineagency',
   'https://wa.me/5491122334455',
   '+54 9 11 2233-4455',
   'contacto@travelonlineagency.test',
   'Av. Siempre Viva 742, Springfield',
   12.5
  );

-- Lenguajes habilitados para la plataforma
INSERT INTO lenguaje (
  id,
  codigo,
  nombre,
  icono,
  habilitado,
  plataforma_id,
  moneda_def_id
) VALUES
  (1, 'es', 'Español', 'flag-es.svg', 1, 1, 2),
  (2, 'en', 'English', 'flag-en.svg', 1, 1, 1);

-- Actualización del lenguaje por defecto de la plataforma (resuelve dependencia circular)
UPDATE plataforma SET language_def_id = 1 WHERE id = 1;

-- Usuarios del entorno de pruebas
INSERT INTO usuario (id, email, roles, password, nombre) VALUES
  (1, 'admin@travelonlineagency.test', '["ROLE_ADMIN"]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Admin General'),
  (2, 'sofia.partner@test.com', '["ROLE_PARTNER"]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Sofía Partner'),
  (3, 'martin.partner@test.com', '[]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Martín Pending');

-- Partners de reservas
INSERT INTO booking_partner (id, habilitado, usuario_id, comision_plataforma) VALUES
  (1, 1, 2, 20.0),
  (2, 0, 3, NULL);

-- Servicios disponibles para los partners
INSERT INTO booking (
  id,
  nombre,
  descripcion,
  detalles,
  form_requerido,
  imagenes,
  disponibles,
  valido_hasta,
  habilitado,
  lenguaje_id,
  fechasdelservicio,
  horaprevia,
  booking_partner_id
) VALUES
  (
    1,
    'City Tour Buenos Aires',
    'Recorrido guiado por los puntos emblemáticos de la ciudad.',
    'Incluye transporte, guía bilingüe y degustación gastronómica en San Telmo.',
    '[]',
    '[{"imagen": "city-tour-ba.jpg", "portada": true}, {"imagen": "obelisco.jpg", "portada": false}]',
    10,
    '2025-12-31 23:59:59',
    1,
    1,
    '[{"fecha": "2025-01-15T09:00", "cantidad": 10}, {"fecha": "2025-01-16T09:00", "cantidad": 8}]',
    24,
    1
  ),
  (
    2,
    'Traslado Aeropuerto Ezeiza',
    'Servicio de traslado privado desde/hacia Aeropuerto Ezeiza.',
    'Chofer bilingüe, vehículo ejecutivo y seguimiento de vuelos en tiempo real.',
    '[]',
    NULL,
    20,
    NULL,
    1,
    1,
    NULL,
    4,
    1
  );

-- Precios asociados a los servicios
INSERT INTO precio (id, valor, moneda_id, booking_id) VALUES
  (1, 120.00, 1, 1),
  (2, 95000.00, 2, 1),
  (3, 65.00, 1, 2);

SET FOREIGN_KEY_CHECKS = 1;

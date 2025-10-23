SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Limpieza de tablas en orden amplio para evitar claves foráneas huérfanas
DELETE FROM detalle_pago_pay_pal;
ALTER TABLE detalle_pago_pay_pal AUTO_INCREMENT = 1;
DELETE FROM pay_pal_pago;
ALTER TABLE pay_pal_pago AUTO_INCREMENT = 1;
DELETE FROM mercado_pago_pago;
ALTER TABLE mercado_pago_pago AUTO_INCREMENT = 1;
DELETE FROM cash_payment;
ALTER TABLE cash_payment AUTO_INCREMENT = 1;
DELETE FROM driver_balance_entry;
ALTER TABLE driver_balance_entry AUTO_INCREMENT = 1;
DELETE FROM driver_withdrawal_request;
ALTER TABLE driver_withdrawal_request AUTO_INCREMENT = 1;
DELETE FROM transfer_assignment;
ALTER TABLE transfer_assignment AUTO_INCREMENT = 1;
DELETE FROM transfer_request_field_value;
ALTER TABLE transfer_request_field_value AUTO_INCREMENT = 1;
DELETE FROM transfer_request_destination;
ALTER TABLE transfer_request_destination AUTO_INCREMENT = 1;
DELETE FROM transfer_request;
ALTER TABLE transfer_request AUTO_INCREMENT = 1;
DELETE FROM transfer_combo_destination;
ALTER TABLE transfer_combo_destination AUTO_INCREMENT = 1;
DELETE FROM transfer_combo;
ALTER TABLE transfer_combo AUTO_INCREMENT = 1;
DELETE FROM transfer_destination;
ALTER TABLE transfer_destination AUTO_INCREMENT = 1;
DELETE FROM transfer_form_field;
ALTER TABLE transfer_form_field AUTO_INCREMENT = 1;
DELETE FROM driver_profile;
ALTER TABLE driver_profile AUTO_INCREMENT = 1;
DELETE FROM solicitud_reserva;
ALTER TABLE solicitud_reserva AUTO_INCREMENT = 1;
DELETE FROM traduccion_estado;
ALTER TABLE traduccion_estado AUTO_INCREMENT = 1;
DELETE FROM estado_reserva;
ALTER TABLE estado_reserva AUTO_INCREMENT = 1;
DELETE FROM traduccion_booking;
ALTER TABLE traduccion_booking AUTO_INCREMENT = 1;
DELETE FROM precio;
ALTER TABLE precio AUTO_INCREMENT = 1;
DELETE FROM booking;
ALTER TABLE booking AUTO_INCREMENT = 1;
DELETE FROM booking_partner;
ALTER TABLE booking_partner AUTO_INCREMENT = 1;
DELETE FROM push_end_point;
ALTER TABLE push_end_point AUTO_INCREMENT = 1;
DELETE FROM respuesta_mensaje;
ALTER TABLE respuesta_mensaje AUTO_INCREMENT = 1;
DELETE FROM mensaje;
ALTER TABLE mensaje AUTO_INCREMENT = 1;
DELETE FROM traduccion_pregunta_frecuente;
ALTER TABLE traduccion_pregunta_frecuente AUTO_INCREMENT = 1;
DELETE FROM pregunta_frecuente;
ALTER TABLE pregunta_frecuente AUTO_INCREMENT = 1;
DELETE FROM traduccion_plataforma;
ALTER TABLE traduccion_plataforma AUTO_INCREMENT = 1;
DELETE FROM lenguaje;
ALTER TABLE lenguaje AUTO_INCREMENT = 1;
DELETE FROM plataforma;
ALTER TABLE plataforma AUTO_INCREMENT = 1;
DELETE FROM credenciales_pay_pal;
ALTER TABLE credenciales_pay_pal AUTO_INCREMENT = 1;
DELETE FROM credenciales_mercado_pago;
ALTER TABLE credenciales_mercado_pago AUTO_INCREMENT = 1;
DELETE FROM usuario;
ALTER TABLE usuario AUTO_INCREMENT = 1;
DELETE FROM moneda;
ALTER TABLE moneda AUTO_INCREMENT = 1;

-- Catálogo de monedas disponible en la plataforma
INSERT INTO moneda (id, nombre, simbolo, habilitada) VALUES
  (1, 'Dólar estadounidense', 'USD', 1),
  (2, 'Peso argentino', 'ARS', 1),
  (3, 'Euro', 'EUR', 1);

-- Credenciales globales de la plataforma
INSERT INTO credenciales_pay_pal (
  id, client_id, client_secret, scope, access_token, token_type,
  app_id, expires_in, nonce, code, refresh_token, created_at, updated_at
) VALUES
  (1,
   'APP-PLATFORM-CLIENT',
   'APP-PLATFORM-SECRET',
   'openid profile payments',
   'A21AAH_PLATFORM_ACCESS_TOKEN',
   'Bearer',
   'APP-PLATFORM-ID',
   28800,
   'nonce-platform-001',
   'auth-code-platform',
   'refresh-token-platform',
   '2024-11-18 09:00:00',
   '2024-11-20 11:00:00'
  );

INSERT INTO credenciales_mercado_pago (
  id, client_id, access_token, public_key, client_secret, code, token_type,
  expires_in, scope, user_id, refresh_token, nickname, email,
  created_at, updated_at, fechavence
) VALUES
  (1,
   'APP-PLATFORM-MP',
   'APP-PLATFORM-ACCESS-TOKEN',
   'APP-PLATFORM-PUBLIC-KEY',
   'APP-PLATFORM-SECRET',
   'auth-code-mp-platform',
   'Bearer',
   21600,
   'read write offline_access',
   '9999999999999999',
   'refresh-token-mp-platform',
   'TOAPlatform',
   'platform@travelonlineagency.test',
   '2024-11-18 09:00:00',
   '2024-11-20 11:00:00',
   '2024-11-20 16:59:00'
  ),
  (2,
   'APP-PARTNER-MP',
   'APP-PARTNER-ACCESS-TOKEN',
   'APP-PARTNER-PUBLIC-KEY',
   'APP-PARTNER-SECRET',
   'auth-code-mp-partner',
   'Bearer',
   21600,
   'read write offline_access',
   '8888888888888888',
   'refresh-token-mp-partner',
   'PartnerSofia',
   'sofia.partner@test.com',
   '2024-11-19 10:00:00',
   '2024-11-20 12:00:00',
   '2024-11-20 18:00:00'
  );

-- Plataforma general de la agencia
INSERT INTO plataforma (
  id, nombre, traslados_od_libres, tasa_traslados_def, language_def_id, moneda_def_id,
  credenciales_pay_pal_id, credenciales_mercado_pago_id,
  logo, icono, link_instagram, link_whatsapp, contacto_telefono,
  contacto_correo, contacto_direccion, comision_booking_partner,
  enable_mercado_pago_payments, enable_pay_pal_payments, enable_cash_payments, cash_payment_instructions
) VALUES
  (1,
   'Travel Online Agency',
   0,
   15.0,
   1,
   1,
   1,
   1,
   'logo-toa.svg',
   'favicon-toa.svg',
   'https://www.instagram.com/travelonlineagency',
   'https://wa.me/5491122334455',
   '+54 9 11 2233-4455',
   'contacto@travelonlineagency.test',
   'Av. Siempre Viva 742, Springfield',
   12.5,
   1,
   1,
   1,
   'Coordiná el pago en efectivo con nuestro equipo de atención o al momento del arribo. Mostrá el comprobante recibido por correo.'
  );

-- Lenguajes habilitados
INSERT INTO lenguaje (
  id, codigo, nombre, icono, habilitado, plataforma_id, moneda_def_id
) VALUES
  (1, 'es', 'Español', 'flag-es.svg', 1, 1, 2),
  (2, 'en', 'English', 'flag-en.svg', 1, 1, 1),
  (3, 'pt', 'Português', 'flag-pt.svg', 1, 1, 3);

-- Traducciones de contenidos globales
INSERT INTO traduccion_plataforma (id, key_name, value, lenguaje_id, plataforma_id) VALUES
  (1, 'homepage.hero_title', 'Discover unique experiences around the world', 2, 1),
  (2, 'homepage.hero_title', 'Descubra experiências únicas pelo mundo', 3, 1),
  (3, 'footer.support', 'Soporte 24/7 para tus viajes.', 1, 1),
  (4, 'footer.support', '24/7 support for your journeys.', 2, 1);

-- Estados de reserva y sus traducciones
INSERT INTO estado_reserva (id) VALUES
  (1), (2), (3);

INSERT INTO traduccion_estado (id, key_name, traduccion, lenguaje_id, estado_reserva_id) VALUES
  (1, 'pending', 'Pendiente', 1, 1),
  (2, 'confirmed', 'Confirmada', 1, 2),
  (3, 'cancelled', 'Cancelada', 1, 3),
  (4, 'pending', 'Pending', 2, 1),
  (5, 'confirmed', 'Confirmed', 2, 2),
  (6, 'cancelled', 'Cancelled', 2, 3),
  (7, 'pending', 'Pendente', 3, 1),
  (8, 'confirmed', 'Confirmada', 3, 2),
  (9, 'cancelled', 'Cancelada', 3, 3);

-- Usuarios de prueba (contraseña: Password123)
INSERT INTO usuario (id, email, roles, password, nombre) VALUES
  (1, 'admin@travelonlineagency.test', '["ROLE_ADMIN"]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Admin General'),
  (2, 'sofia.partner@test.com', '["ROLE_PARTNER"]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Sofía Partner'),
  (3, 'martin.partner@test.com', '[]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Martín Pending'),
  (4, 'cliente@travelonlineagency.test', '[]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Carla Cliente'),
  (5, 'driver@travelonlineagency.test', '["ROLE_DRIVER"]', '$2y$12$VBCo/LlELFHPMDJZpchcKO6ArWDWXvEtjOtV4qcsbzLfUMfEg3xRm', 'Diego Chofer');

-- Suscripciones push registradas
INSERT INTO push_end_point (id, usuario_id, suscripcion, created_at) VALUES
  (1, 1, '{"endpoint":"https://push.travelonlineagency.test/admin","keys":{"p256dh":"AAA","auth":"111"}}', '2024-11-20 10:00:00'),
  (2, 2, '{"endpoint":"https://push.travelonlineagency.test/partner","keys":{"p256dh":"BBB","auth":"222"}}', '2024-11-20 10:05:00');

-- Partners de reservas
INSERT INTO booking_partner (id, habilitado, usuario_id, comision_plataforma, mercado_pago_cuenta_id) VALUES
  (1, 1, 2, 20.0, 2),
  (2, 0, 3, NULL, NULL);

-- Choferes registrados
INSERT INTO driver_profile (
  id, usuario_id, nombre_completo, documento, telefono, patente, modelo_vehiculo,
  foto_vehiculo, aprobado, notas, commission_percentage, cbu, cvu, bank_alias, creado_en, actualizado_en
) VALUES
  (1, 5, 'Diego Chofer', '32.123.456', '+54 9 3757 111222', 'AB123CD', 'Toyota Corolla 2022',
   'driver-diego.jpg', 1, 'Disponible para traslados aeropuerto-hotel.', 15.00,
   '1230001230001230001234', '0000000000000000000001', 'chofer.diego', '2024-11-20 09:00:00', '2024-11-20 09:00:00');

-- Movimientos iniciales del chofer
INSERT INTO driver_balance_entry (
  id, driver_id, amount, currency, direction, type, description, reference, created_at, created_by_id, cash_payment_id
) VALUES
  (1, 1, 180.00, 'ARS', 'credit', 'earning', 'Ingreso por traslado aeropuerto-hotel', 'transfer-earning-1', '2024-11-20 12:00:00', NULL, NULL),
  (2, 1, 80.00, 'ARS', 'debit', 'cash_delivery', 'Entrega de efectivo al administrador', 'cash-transfer-1', '2024-11-20 18:30:00', NULL, NULL);

-- Solicitudes de retiro de ejemplo
INSERT INTO driver_withdrawal_request (
  id, driver_id, amount, currency, status, notes, admin_notes, created_at, updated_at, processed_at, requested_by_id, processed_by_id
) VALUES
  (1, 1, 60.00, 'ARS', 'pending', 'Retiro inicial para viáticos', NULL, '2024-11-21 09:00:00', '2024-11-21 09:00:00', NULL, 5, NULL);

-- Servicios disponibles
INSERT INTO booking (
  id, nombre, descripcion, detalles, form_requerido, imagenes, disponibles,
  valido_hasta, habilitado, lenguaje_id, fechasdelservicio, horaprevia, booking_partner_id
) VALUES
  (1,
   'City Tour Buenos Aires',
   'Recorrido guiado por los puntos emblemáticos de la ciudad.',
   'Incluye transporte, guía bilingüe y degustación gastronómica en San Telmo.',
   '[{"field":"hotel","label":"Hotel"},{"field":"flight","label":"Vuelo"}]',
   '[{"imagen": "city-tour-ba.jpg", "portada": true}, {"imagen": "obelisco.jpg", "portada": false}]',
   10,
   '2025-12-31 23:59:59',
   1,
   1,
   '[{"fecha": "2025-01-15T09:00", "cantidad": 10}, {"fecha": "2025-01-16T09:00", "cantidad": 8}]',
   24,
   1
  ),
  (2,
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
  ),
  (3,
   'Glaciar Perito Moreno Full Day',
   'Excursión completa al glaciar con navegación y trekking opcional.',
   'Incluye traslado, guía especializado y acceso a pasarelas panorámicas.',
   '[{"field":"document","label":"Documento"}]',
   '[{"imagen":"perito-moreno.jpg","portada":true}]',
   15,
   '2025-09-30 23:59:59',
   1,
   1,
   '[{"fecha":"2025-02-10T07:00","cantidad":15}]',
   48,
   NULL
  );

-- Traducciones de servicios
INSERT INTO traduccion_booking (id, nombre, descripcion, detalles, lenguaje_id, booking_id) VALUES
  (1, 'Buenos Aires City Tour', 'Guided tour through the iconic spots of the city.', 'Includes transportation, bilingual guide and food tasting in San Telmo.', 2, 1),
  (2, 'City Tour Buenos Aires', 'Passeio guiado pelos pontos emblemáticos da cidade.', 'Inclui transporte, guia bilíngue e degustação gastronômica em San Telmo.', 3, 1),
  (3, 'Ezeiza Airport Transfer', 'Private transfer service to/from Ezeiza Airport.', 'Bilingual driver, executive vehicle and live flight tracking.', 2, 2),
  (4, 'Perito Moreno Glacier Full Day', 'Full day experience at the glacier with navigation.', 'Transfers, specialized guide and panoramic walkways included.', 2, 3);

-- Precios asociados
INSERT INTO precio (id, valor, moneda_id, booking_id) VALUES
  (1, 120.00, 1, 1),
  (2, 95000.00, 2, 1),
  (3, 65.00, 1, 2),
  (4, 75000.00, 2, 2),
  (5, 210.00, 1, 3);

-- Preguntas frecuentes y traducciones
INSERT INTO pregunta_frecuente (id, titulo, respuesta, lenguaje_defecto_id) VALUES
  (1, '¿Cómo funcionan las reservas?', 'Completá el formulario y recibí la confirmación por email.', 1),
  (2, '¿Puedo cancelar sin costo?', 'Las cancelaciones son gratuitas hasta 48 hs antes del servicio.', 1);

INSERT INTO traduccion_pregunta_frecuente (id, titulo, respuesta, lenguaje_id, pregunta_frecuente_id) VALUES
  (1, 'How do bookings work?', 'Fill the form and get your confirmation by email.', 2, 1),
  (2, 'Can I cancel for free?', 'Cancellations are free up to 48 hours before the service.', 2, 2),
  (3, 'Como funcionam as reservas?', 'Complete o formulário e receba a confirmação por e-mail.', 3, 1);

-- Mensajes de contacto y respuestas
INSERT INTO mensaje (id, nombre, email, mensaje, created_at, session_id) VALUES
  (1, 'Laura Cliente', 'laura@example.com', 'Quisiera saber si hay disponibilidad para 4 personas.', '2024-11-21 14:30:00', 'session-abc'),
  (2, 'Diego Consultas', 'diego@example.com', '¿Aceptan pagos en cuotas?', '2024-11-21 15:00:00', 'session-def');

INSERT INTO respuesta_mensaje (id, mensaje_contacto_id, mensaje, created_at, usuario_id) VALUES
  (1, 1, 'Hola Laura, tenemos cupos para tu fecha seleccionada.', '2024-11-21 16:00:00', 1),
  (2, 2, 'Hola Diego, aceptamos cuotas con Mercado Pago y PayPal.', '2024-11-21 16:30:00', 1);

-- Solicitudes de reserva
INSERT INTO solicitud_reserva (
  id, name, surname, email, phone, form_required, in_charge_of, canceled,
  created_at, updated_at, booking_id, fecha_seleccionada, estado_id, idioma_preferido_id
) VALUES
  (1,
   'Carla',
   'Gómez',
   'carla@example.com',
   5491122334455,
   '[{"field":"hotel","value":"Alvear"}]',
   '[{"name":"Carla","dni":"12345678"}]',
   0,
   '2024-11-19 09:00:00',
   '2024-11-19 09:00:00',
   1,
   '2025-01-15 09:00:00',
   2,
   1
  ),
  (2,
   'Lucas',
   'Pereyra',
   'lucas@example.com',
   5491166677788,
   '[]',
   '[{"name":"Lucas","dni":"99887766"}]',
   0,
   '2024-11-20 10:15:00',
   '2024-11-20 10:30:00',
   2,
   '2024-12-05 08:00:00',
   1,
   2
  ),
(3,
   'María',
   'Souza',
   'maria@example.com',
   551199887766,
   '[{"field":"document","value":"RG12345"}]',
   '[{"name":"María","passport":"BR987654"}]',
   0,
   '2024-11-18 08:45:00',
   '2024-11-18 08:45:00',
   3,
   '2025-02-10 07:00:00',
   2,
   3
  );

-- Catálogo de destinos de traslado con categorías y coordenadas
DELETE FROM transfer_destination_category;
ALTER TABLE transfer_destination_category AUTO_INCREMENT = 1;
INSERT INTO transfer_destination_category (id, nombre, icono) VALUES
  (1, 'Parque Natural', '<i class="bi bi-tree"></i>'),
  (2, 'Aventura', '<i class="bi bi-compass"></i>'),
  (3, 'Cultura', '<i class="bi bi-bank"></i>'),
  (4, 'Gastronomía', '<i class="bi bi-cup-straw"></i>');

DELETE FROM transfer_destination;
ALTER TABLE transfer_destination AUTO_INCREMENT = 1;
INSERT INTO transfer_destination (
  id, nombre, direccion, coordenadas_lat, coordenadas_lng, categoria_id,
  descripcion_corta, descripcion, tarifa_base, activo, imagen_portada
) VALUES
  (1,
   'Parque Nacional Iguazú',
   'Acceso principal por Ruta Nacional 101, Puerto Iguazú',
   -25.67875,
   -54.44461,
   1,
   'El hogar de las Cataratas, senderos selváticos y fauna autóctona.',
   '<p>Explorá las pasarelas superior e inferior, la Garganta del Diablo y los circuitos náuticos. El parque ofrece servicios de gastronomía, tiendas de recuerdos y traslados internos.</p>',
   25.00,
   1,
   'parque-nacional.svg'
  ),
  (2,
   'Hito Tres Fronteras',
   'Avenida Costanera s/n, Puerto Iguazú',
   -25.59973,
   -54.58032,
   3,
   'Mirador icónico donde convergen Argentina, Brasil y Paraguay.',
   '<p>Disfrutá del show de aguas danzantes al atardecer, feria de artesanías y gastronomía regional.</p>',
   18.50,
   1,
   'hito-tres-fronteras.svg'
  ),
  (3,
   'Güirá Oga',
   'Ruta Nacional 12 km 5, Puerto Iguazú',
   -25.60658,
   -54.53512,
   1,
   'Refugio de fauna misionera con visitas guiadas para toda la familia.',
   '<p>Centro de rehabilitación de animales silvestres rescatados. Ideal para descubrir la biodiversidad de la región.</p>',
   22.00,
   1,
   'guira-oga.svg'
  ),
  (4,
   'Jardín de los Picaflores',
   'Fray Luis Beltrán 150, Puerto Iguazú',
   -25.60031,
   -54.57309,
   4,
   'Pequeño santuario urbano con más de 15 especies de colibríes.',
   '<p>Una experiencia íntima para observar aves y disfrutar de un café en medio de la vegetación.</p>',
   15.00,
   1,
   'jardin-picaflores.svg'
  ),
  (5,
   'Duty Free Shop Iguazú',
   'Ruta Nacional 12 km 1645, Puerto Iguazú',
   -25.59788,
   -54.56842,
   4,
   'Centro comercial libre de impuestos con marcas premium.',
   '<p>Abierto todos los días con propuestas gastronómicas, perfumería, tecnología y moda.</p>',
   20.00,
   1,
   'duty-free-iguazu.svg'
  );

-- Combos preconfigurados por el administrador
INSERT INTO transfer_combo (id, nombre, descripcion, precio, activo, imagen_portada) VALUES
  (1, 'Aeropuerto a Hotel ida', 'Servicio directo desde el aeropuerto hasta el hotel seleccionado.', 40.00, 1, 'combo-airport.jpg'),
  (2, 'Aeropuerto + City Tour', 'Traslado combinado al hotel y visita al Hito Tres Fronteras.', 55.00, 1, 'combo-city.jpg');

INSERT INTO transfer_combo_destination (id, combo_id, destino_id, posicion) VALUES
  (1, 1, 1, 1),
  (2, 1, 2, 2),
  (3, 2, 1, 1),
  (4, 2, 3, 2);

-- Campos adicionales configurables
INSERT INTO transfer_form_field (id, clave, etiqueta, tipo, requerido, opciones, orden) VALUES
  (1, 'vuelo', 'Número de vuelo', 'text', 1, NULL, 1),
  (2, 'pasajeros', 'Cantidad de pasajeros', 'number', 0, NULL, 2);

-- Solicitudes de traslado de ejemplo
INSERT INTO transfer_request (
  id, combo_id, usuario_id, tipo, precio_total, moneda,
  nombre_pasajero, email_pasajero, telefono_pasajero,
  arribo, salida, estado, datos_extra, token_seguimiento,
  notas_cliente, creado_en, actualizado_en
) VALUES
  (1, 1, 4, 'combo', 40.00, 'ARS', 'Carla Cliente', 'carla@example.com', '+54 9 3757 333444',
   '2024-12-01 14:30:00', '2024-12-05 11:00:00', 'en_curso', '{"vuelo":"AR1820"}', 'TRK123ABCDEF', 'Llega con equipaje voluminoso.',
   '2024-11-20 08:30:00', '2024-11-20 10:00:00'),
  (2, NULL, 4, 'custom', 40.00, 'ARS', 'Lucas Pereyra', 'lucas@example.com', '+54 9 3757 555666',
   '2024-12-10 09:00:00', '2024-12-12 18:00:00', 'pendiente', '{"pasajeros":"3"}', 'TRK456DEFABC', 'Agregar silla para niño.',
   '2024-11-21 09:45:00', '2024-11-21 09:45:00');

INSERT INTO transfer_request_destination (id, solicitud_id, destino_id, posicion) VALUES
  (1, 1, 1, 1),
  (2, 1, 2, 2),
  (3, 2, 1, 1),
  (4, 2, 3, 2);

INSERT INTO transfer_request_field_value (id, solicitud_id, campo_id, valor) VALUES
  (1, 1, 1, 'AR1820'),
  (2, 2, 2, '3');

INSERT INTO transfer_assignment (
  id, solicitud_id, chofer_id, estado, parada_actual, notas, creado_en, actualizado_en, finalizado_en
) VALUES
  (1, 1, 1, 'en_curso', 1, 'Pasajero ya abordó en el aeropuerto.', '2024-11-20 09:10:00', '2024-11-20 10:05:00', NULL);

-- Pagos de Mercado Pago asociados
INSERT INTO mercado_pago_pago (
  id, payment_id, preference_id, credenciales_mercado_pago_id, status, collector_id,
  payer, transaction_amount, transaction_amount_refunded, payment_method_id,
  payment_type_id, card, net_received_amount, fee_details, application_fee,
  created_at, updated_at, solicitud_reserva_id
) VALUES
  (1,
   778899001122,
   'PREF-BA-001',
   2,
   'approved',
   '8888888888888888',
   '{"email":"payer.one@example.com","first_name":"Carla"}',
   240.00,
   0.00,
   'visa',
   'credit_card',
   '{"last_four_digits":"4242"}',
   204.00,
   '[{"type":"application_fee","amount":36.00}]',
   36.00,
   '2024-11-19 09:05:00',
   '2024-11-19 09:05:00',
   1
  ),
  (2,
   778899001133,
   'PREF-TR-002',
   1,
   'pending',
   '9999999999999999',
   '{"email":"payer.two@example.com","first_name":"Lucas"}',
   90.00,
   0.00,
   'master',
   'account_money',
   NULL,
   75.00,
   '[{"type":"application_fee","amount":15.00}]',
   15.00,
   '2024-11-20 10:40:00',
  '2024-11-20 10:40:00',
  2
  );

INSERT INTO mercado_pago_pago (
  id, payment_id, preference_id, credenciales_mercado_pago_id, status, collector_id,
  payer, transaction_amount, transaction_amount_refunded, payment_method_id,
  payment_type_id, card, net_received_amount, fee_details, application_fee,
  created_at, updated_at, solicitud_reserva_id, transfer_request_id
) VALUES
  (3,
   778899001155,
   'PREF-TR-003',
   1,
   'approved',
   '9999999999999999',
   '{"email":"carla@example.com","first_name":"Carla"}',
   40.00,
   0.00,
   'visa',
   'credit_card',
   NULL,
   40.00,
   '[]',
   NULL,
   '2024-11-20 10:15:00',
   '2024-11-20 10:15:00',
   NULL,
   1
  );

-- Pagos de PayPal y sus detalles
INSERT INTO pay_pal_pago (
  id, orders_id, solicitud_reserva_id, credenciales_pay_pal_id,
  created_at, updated_at, estado, total
) VALUES
  (1,
   'ORDER-123PAYPAL',
   3,
   1,
   '2024-11-18 09:00:00',
   '2024-11-18 09:10:00',
   'COMPLETED',
   210.50
  );

INSERT INTO pay_pal_pago (
  id, orders_id, solicitud_reserva_id, transfer_request_id, credenciales_pay_pal_id,
  created_at, updated_at, estado, total
) VALUES
  (2,
   'ORDER-TRANSFER-001',
   NULL,
   2,
   1,
   '2024-11-21 10:00:00',
   '2024-11-21 10:05:00',
   'PAYER_ACTION_REQUIRED',
   40.00
  );

-- Pagos en efectivo registrados
INSERT INTO cash_payment (
  id, solicitud_reserva_id, transfer_request_id, amount, currency, status,
  notes, created_at, updated_at, reference, driver_reported_at, admin_confirmed_at, driver_reported_by_id, driver_balance_entry_id
) VALUES
  (1,
   2,
   NULL,
   250.00,
   'ARS',
   'pending',
   'El huésped abonará en efectivo al momento del check-in.',
   '2024-11-21 11:00:00',
   '2024-11-21 11:00:00',
   'booking-2',
   NULL,
   NULL,
   NULL,
   NULL
  ),
  (2,
   NULL,
   1,
   90.00,
   'ARS',
   'driver_reported',
   'Coordinar cobro en efectivo con el chofer asignado.',
   '2024-11-21 11:30:00',
   '2024-11-21 11:45:00',
   'transfer-1',
   '2024-11-21 12:00:00',
   NULL,
   1,
   2
  );

INSERT INTO detalle_pago_pay_pal (
  id, pay_pal_pago_id, capture_id, created_at, updated_at, seller_receivable_breakdown
) VALUES
  (1,
   1,
   'CAPTURE-XYZ-001',
   '2024-11-18 09:05:00',
   '2024-11-18 09:10:00',
   '{"gross_amount":210.50,"paypal_fee":6.30,"net_amount":204.20}'
  );


SET FOREIGN_KEY_CHECKS = 1;

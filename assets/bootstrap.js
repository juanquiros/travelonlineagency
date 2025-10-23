import { startStimulusApp } from '@symfony/stimulus-bundle';
import PartnerServiceFormController from './controllers/partner_service_form_controller.js';
import PushNotificationsController from './controllers/push_notifications_controller.js';
import TinymceController from './controllers/tinymce_controller.js';
import TransferDestinationMapController from './controllers/transfer_destination_map_controller.js';
import TransferDestinationsOverviewController from './controllers/transfer_destinations_overview_controller.js';
import DestinosMapController from './controllers/destinos_map_controller.js';
import DestinoMapController from './controllers/destino_map_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('partner-service-form', PartnerServiceFormController);
app.register('push-notifications', PushNotificationsController);
app.register('tinymce', TinymceController);
app.register('transfer-destination-map', TransferDestinationMapController);
app.register('transfer-destinations-overview', TransferDestinationsOverviewController);
app.register('destinos-map', DestinosMapController);
app.register('destino-map', DestinoMapController);

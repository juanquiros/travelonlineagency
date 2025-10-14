import { startStimulusApp } from '@symfony/stimulus-bundle';
import PartnerServiceFormController from './controllers/partner_service_form_controller.js';
import PushNotificationsController from './controllers/push_notifications_controller.js';
import TinymceController from './controllers/tinymce_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('partner-service-form', PartnerServiceFormController);
app.register('push-notifications', PushNotificationsController);
app.register('tinymce', TinymceController);

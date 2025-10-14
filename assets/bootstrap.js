import { startStimulusApp } from '@symfony/stimulus-bundle';
import PartnerServiceFormController from './controllers/partner_service_form_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('partner-service-form', PartnerServiceFormController);

import './file-upload';
import TwintUploadService from "./file-upload";
import TwintPaymentSettingsService from './twint-payment-settings';
import TwintPaymentService from './twint-payment';

const {Application} = Shopware;

const httpClient = Application.getContainer('init').httpClient;
const loginService = Shopware.Service('loginService');

Application.addServiceProvider('twintFileUploadService', () => {
    return new TwintUploadService(httpClient, loginService);
});

Application.addServiceProvider('TwintPaymentSettingsService', (container) => {
    return new TwintPaymentSettingsService(httpClient, loginService);
});

Application.addServiceProvider('TwintPaymentService', (container) => {
    return new TwintPaymentService(httpClient, loginService);
});
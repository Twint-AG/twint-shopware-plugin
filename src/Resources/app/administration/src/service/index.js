import './file-upload';
import TwintUploadService from "./file-upload";

const {Application} = Shopware;

Application.addServiceProvider('twintFileUploadService', () => {
    return new TwintUploadService(
        Shopware.Application.getContainer('init').httpClient,
        Shopware.Service('loginService'),
    );
});

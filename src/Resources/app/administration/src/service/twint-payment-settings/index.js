const ApiService = Shopware.Classes.ApiService;

export default class TwintPaymentSettingsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'twint') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateCredentials(credential) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_actions/twint/validate-api-credential`,
                credential,
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}


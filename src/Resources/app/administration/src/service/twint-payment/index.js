const ApiService = Shopware.Classes.ApiService;

export default class TwintPaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'twint') {
        super(httpClient, loginService, apiEndpoint);
    }

    refund(data) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_actions/twint/refund`,
                data,
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    orderStatus(orderId) {
        const headers = this.getBasicHeaders();
        return this.httpClient
            .get(
                `_actions/twint/order/${orderId}/status`,
                {
                    headers,
                },
            ).then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getPairing(orderId) {
        const headers = this.getBasicHeaders();
        return this.httpClient
          .get(
            `_actions/twint/order/${orderId}/pairing`,
            {
                headers,
            },
          ).then((response) => {
              return ApiService.handleResponse(response);
          });
    }
}


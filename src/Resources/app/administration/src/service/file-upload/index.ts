const ApiService = Shopware.Classes.ApiService;

export default class TwintUploadService extends ApiService {
  constructor(httpClient, loginService, apiEndpoint = 'twint') {
    super(httpClient, loginService, apiEndpoint);
  }

  uploadFile(file, password) {
    const headers = this.getBasicHeaders();
    headers['Content-Type'] = 'multipart/form-data';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('password', password);

    return this.httpClient.post(
      `_actions/twint/extract-pem`,
      formData,
      {
        headers,
      }
    );
  }
}

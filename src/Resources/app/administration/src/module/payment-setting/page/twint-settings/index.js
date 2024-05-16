import template from './twint-settings.html.twig';

const {Mixin} = Shopware;

export default {
    template,

    inject: ['TwintPaymentSettingsService'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    data() {
        return {
            isLoading: false,
            isTesting: false,
            isSaveSuccessful: false,
            isTestSuccessful: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        onChanged(config) {
            this.isTestSuccessful = false;
            this.isSaveSuccessful = false;
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        getConfigValue(field) {
            const actualConfig = this.$refs.systemConfig.actualConfigData;
            const defaultConfig = actualConfig.null;
            const salesChannelId = this.$refs.systemConfig.currentSalesChannelId;

            if (salesChannelId === null) {
                return actualConfig.null[`TwintPayment.settings.${field}`];
            }

            let value =  actualConfig[salesChannelId][`TwintPayment.settings.${field}`];
            if(value === undefined || value === null) {
                value = defaultConfig[`TwintPayment.settings.${field}`];
            }

            return value;
        },

        onSave() {
            if (!this.checkRequiredFields()) {
                return;
            }

            this.isSaveSuccessful = false;
            this.isLoading = true;
            this.$refs.systemConfig.saveAll().then((response) => {
                this.isSaveSuccessful = true;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        isValidUUIDv4(uuid) {
            // Regular expression to match UUID v4 format
            var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

            // Check if the string matches the UUID v4 format
            return uuidRegex.test(uuid);
        },

        checkRequiredFields() {
            let isValid = true;
            const merchantId = this.getConfigValue('merchantId');
            const certificate = this.getConfigValue('certificate');

            if (!merchantId || merchantId.trim() === '') {
                this.createNotificationError({
                    title: this.$tc('twint.settings.merchantId.error.title'),
                    message: this.$tc('twint.settings.merchantId.error.required')
                });

                isValid = false;
            }

            if (isValid && !this.isValidUUIDv4(merchantId)) {
                this.createNotificationError({
                    title: this.$tc('twint.settings.merchantId.error.title'),
                    message: this.$tc('twint.settings.merchantId.error.invalidFormat')
                });

                isValid = false;
            }

            if (!certificate) {
                this.createNotificationError({
                    title: this.$tc('twint.settings.merchantId.error.title'),
                    message: this.$tc('twint.settings.certificate.error.required')
                });

                isValid = false;
            }

            return isValid;
        },

        onTest() {
            if (!this.checkRequiredFields()) {
                return;
            }

            this.isTesting = true;
            this.isTestSuccessful = false;

            let credential = {};
            this.$refs.systemConfig.config.forEach((cards) => {
                credential = {
                    cert: this.getConfigValue('certificate'),
                    merchantId: this.getConfigValue('merchantId'),
                    testMode: this.getConfigValue('testMode'),
                };
            });

            this.TwintPaymentSettingsService.validateCredential(credential).then((response) => {
                const success = response.success ?? false;

                if (success) {
                    this.isTestSuccessful = true;
                    this.onSave();
                } else {
                    this.createNotificationError({
                        title: this.$tc('twint.settings.testCredentials.error.title'),
                        message: this.$tc('twint.settings.testCredentials.error.message')
                    });
                }
            }).finally((errorResponse) => {
                this.isTesting = false;
            });
        }
    }
};

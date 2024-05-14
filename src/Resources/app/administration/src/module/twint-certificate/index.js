import template from './twint-certificate.html.twig';
import template65 from './twint-certificate-65.html.twig';
import './twint-certificate.scss';

const {Component, Mixin} = Shopware;

Component.register('twint-certificate', {
    template: Shopware.Feature.isActive('v6.6.0.0') ? template : template65,

    mixins: [
        Mixin.getByName('notification'),
    ],
    inject: ['feature'],
    data() {
        return {
            currentPassword: null,
            currentCertFile: null,
        };
    },

    methods: {
        onFileChange(file) {
            this.currentCertFile = file;
            this.extractPem();
        },

        updatePassword(event) {
            this.extractPem();
        },

        extractPem() {
            const service = Shopware.Service('twintFileUploadService');
            if (!this.currentCertFile || !this.currentPassword || this.currentPassword.length === 0) {
                return;
            }

            service.uploadFile(this.currentCertFile, this.currentPassword ?? '').then((res) => {
                this.updateCertificate(res.data.data);
                this.createNotification({
                    title: "Success",
                    message: this.$tc('twint.certificateSuccess'),
                    growl: true
                }).then(r => {

                });

            }).catch((err) => {
                //specific error handling
                if (err.response.status === 400) {
                    let errorCode = err.response.data.errorCode;

                    return this.createNotificationError({
                        title: this.$tc('twint.certificateErrorTitle'),
                        message: this.$tc('twint.certificateError.' + errorCode),
                        growl: true
                    });
                }

                // Generic error handling
                this.createNotificationError({
                    title: this.$tc('twint.certificateErrorTitle'),
                    message: this.$tc('twint.validation.errorMessage'),
                    growl: true
                });
            })
        },

        updateCertificate(value) {
            if (this.feature.isActive('v6.6.0.0')) {
                this.$emit('update:value', value);
                return;
            }

            this.$emit('input', value);
        }
    }
});

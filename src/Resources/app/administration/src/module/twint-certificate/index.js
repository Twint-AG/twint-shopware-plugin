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
            passwordError: false
        };
    },

    methods: {
        onFileChange(file) {
            this.currentCertFile = file;
            if (this.currentCertFile && (!this.currentPassword || this.currentPassword.length === 0)) {
                this.passwordError = true;
                this.$root.$emit('update-lock', true);
                return;
            }
            else if(!this.currentCertFile){
                this.passwordError = false;
                this.$root.$emit('update-lock', false);
                return;
            }
            this.extractPem();
        },

        updatePassword(event) {
            if (this.currentCertFile && (!this.currentPassword || this.currentPassword.length === 0)) {
                this.passwordError = true;
                this.$root.$emit('update-lock', true);
            }
            else if(this.currentCertFile){
                this.passwordError = false;
                this.extractPem();
            }
        },

        extractPem() {
            const service = Shopware.Service('twintFileUploadService');
            if (!this.currentCertFile || !this.currentPassword || this.currentPassword.length === 0) {
                return;
            }

            service.uploadFile(this.currentCertFile, this.currentPassword ?? '').then((res) => {
                this.updateCertificate(res.data.data);
                this.createNotification({
                    title: this.$tc('twint.settings.certificate.success.title'),
                    message: this.$tc('twint.settings.certificate.success.message'),
                    growl: true
                }).then(r => {
                    this.$root.$emit('update-lock', false);
                });

            }).catch((err) => {
                //specific error handling
                if (err.response.status === 400) {
                    let errorCode = err.response.data.errorCode;

                    return this.createNotificationError({
                        title: this.$tc('twint.settings.certificate.error.title'),
                        message: this.$tc('twint.settings.certificate.error.' + errorCode),
                        growl: true
                    });
                }

                // Generic error handling
                this.createNotificationError({
                    title: this.$tc('twint.settings.certificate.error.title'),
                    message: this.$tc('twint.settings.certificate.error.general'),
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

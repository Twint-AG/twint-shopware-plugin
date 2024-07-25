import template from './twint-certificate.html.twig';
import template65 from './twint-certificate-65.html.twig';
import './twint-certificate.scss';

const {Component, Mixin} = Shopware;
const { ShopwareError } = Shopware.Classes;


Component.register('twint-certificate', {
    template: Shopware.Feature.isActive('v6.6.0.0') ? template : template65,

    mixins: [
        Mixin.getByName('notification'),
    ],
    inject: ['feature', 'systemConfigApiService'],
    data() {
        return {
            currentPassword: null,
            currentCertFile: null,
            passwordError: null,
            validatePassword: '',
            merchantId: null,
            certificate: null,
            validated: null,
            buttonSelector: '.sw-file-input__dropzone .sw-file-input__button',
        };
    },
    created() {
        this.loadSettings();
    },
    methods: {
        onFileChange(file) {
            this.certificate = null;
            this.currentCertFile = file;
            if (this.currentCertFile && (!this.currentPassword || this.currentPassword.length === 0)) {
                this.passwordError = new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
                this.$root.$emit('update-lock', true);
                return;
            }
            else if(!this.currentCertFile){
                this.passwordError = null;
                this.$root.$emit('update-lock', false);
                return;
            }
            this.extractPem();
        },

        updatePassword(event) {
            if (this.currentCertFile && (!this.currentPassword || this.currentPassword.length === 0)) {
                this.passwordError = new ShopwareError({
                    code: 'c1051bb4-d103-4f74-8988-acbcafc7fdc3',
                });
                this.$root.$emit('update-lock', true);
            }
            else if(this.currentCertFile){
                this.passwordError = null;
                this.extractPem();
            }
        },

        extractPem() {
            const service = Shopware.Service('twintFileUploadService');
            if (!this.currentCertFile || !this.currentPassword || this.currentPassword.length === 0) {
                return;
            }
            const inputElement = this.$refs.myInput;
            service.uploadFile(this.currentCertFile, this.currentPassword ?? '').then((res) => {
                this.updateCertificate(res.data.data);
                this.certificate = res.data.data;
                this.changeButtonText();
                this.createNotification({
                    title: this.$tc('twint.settings.certificate.success.title'),
                    message: this.$tc('twint.settings.certificate.success.message'),
                    growl: true
                }).then(r => {
                    this.$root.$emit('update-lock', false);
                });

            }).catch((err) => {
                this.certificate = null;
                this.changeButtonText();
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
            this.changeButtonText();
        },

        async loadSettings() {
            this.isLoading = true;

            const settings = await this.systemConfigApiService.getValues('TwintPayment.settings');
            if (Object.keys(settings).length > 0) {
                this.merchantId = settings['TwintPayment.settings.merchantId'];
                this.certificate = settings['TwintPayment.settings.certificate'];
                this.validated = settings['TwintPayment.settings.validated'];

            }
            this.changeButtonText();
            this.isLoading = false;
        },

        changeButtonText() {
            this.button = document.querySelector(this.buttonSelector);
            if(this.button){
                if(this.certificate != null && this.certificate != ''){
                    this.button.textContent = this.$tc('twint.settings.certificate.button.label');
                }
                else{
                    this.button.textContent = this.$tc('global.sw-file-input.buttonChoose');
                }
            }
        }
    }
});

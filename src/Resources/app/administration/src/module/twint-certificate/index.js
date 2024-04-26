import template from './twint-certificate.html.twig';
import './twint-certificate.scss';

const { Component, Mixin } = Shopware;

Component.register('twint-certificate', {
    template: template,

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

    methods:{
        onFileChange(file){
            this.currentCertFile = file;
            this.extractPem();
        },

        updatePassword(event){
            this.extractPem();
        },

        extractPem(){
            const service = Shopware.Service('twintFileUploadService');
            if(!this.currentCertFile){
                return;
            }

            service.uploadFile(this.currentCertFile , this.currentPassword ?? '').then((res) => {
                this.updateCertificate(res.data.data);
                this.createNotification({
                    title: "Success",
                    message: this.$tc('twint.certificateSuccess'),
                    growl: true
                }).then(r => {

                });

            }).catch((err) => {
                this.createNotificationError({
                    title: this.$tc('twint.validation.errorTitle'),
                    message: this.$tc('twint.validation.errorMessage'),
                    growl: true
                });
            })
        },

        updateCertificate(value){
            if (this.feature.isActive('v6.6.0.0')) {
                this.$emit('update:value', value);
                return;
            }

            this.$emit('input', value);
        }
    }
});

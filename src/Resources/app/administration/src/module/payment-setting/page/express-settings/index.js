import template from './express-settings.html.twig';

const {Mixin} = Shopware;

export default {
    template,
    mixins: [
        Mixin.getByName('sw-inline-snippet')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        onChanged(config) {
            this.isSaveSuccessful = false;
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        onSave() {
            this.isSaveSuccessful = false;
            this.isLoading = true;
            this.$refs.systemConfig.saveAll().then((response) => {
                this.isSaveSuccessful = true;
            }).finally(() => {
                this.isLoading = false;
            });
        },
    }
};

import Feature from './feature.helper';

const { Mixin } = Shopware;

Mixin.register('twint-version', {
    computed: {
        isShopwareGte67() {
            return Feature.isActive('6.7.0.0');
        },

        cardComponent() {
            return this.isShopwareGte67 ? 'mt-card' : 'sw-card';
        },
    },
});
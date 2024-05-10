import template from './twint-payment-plugin-icon.html.twig';
import './twint-payment-plugin-icon.scss';

export default {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
};

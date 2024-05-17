import template from './sw-order-details-state-card.html.twig';
import './sw-order-details-state-card.scss';

// Override your template here, using the actual template from the core
Shopware.Component.override('sw-order-details-state-card', {
    template,
    methods:{
        onShowTwintTransactionLogs() {
            this.$
            this.$emit('show-twint-transaction-logs');
        },
    }
});

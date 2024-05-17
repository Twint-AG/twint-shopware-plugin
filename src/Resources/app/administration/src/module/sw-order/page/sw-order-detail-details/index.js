import template from './sw-order-detail-details.html.twig';

// Override your template here, using the actual template from the core
Shopware.Component.override('sw-order-detail-details', {
    template,
    data() {
        return {
            customFieldSets: [],
            showStateHistoryModal: false,
            showTwintTransactionLogModal: false
        };
    },
});

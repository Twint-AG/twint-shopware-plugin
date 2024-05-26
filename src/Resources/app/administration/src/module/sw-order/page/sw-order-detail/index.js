import template from './sw-order-detail.html.twig';

// Override your template here, using the actual template from the core
Shopware.Component.override('sw-order-detail', {
    template,
    data() {
        return {
            customFieldSets: [],
            showStateHistoryModal: false
        };
    },
});

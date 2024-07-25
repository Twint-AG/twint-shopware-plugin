const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-order-create-options', {

    computed: {

        paymentMethodCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('active', 1));

            if (this.salesChannelId) {
                criteria.addFilter(Criteria.equals('salesChannels.id', this.salesChannelId));
            }
            criteria.addFilter(Criteria.not(
                'AND',
                [Criteria.contains('handlerIdentifier', 'Twint')],
            ));
            return criteria;
        },
    }
});
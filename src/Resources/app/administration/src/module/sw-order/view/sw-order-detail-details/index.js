const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-order-detail-details', {
    inject: [
        'repositoryFactory',
        'acl',
    ],
    data() {
        return {
            twintCustomFieldSetName: 'twint_payment_custom_field_set'
        }
    },
    methods: {
        createdComponent() {
            this.$super('createdComponent');
        }
    },
    computed: {

        customFieldSetCriteria() {
            const criteria = new Criteria(1, null);
            criteria.addFilter(Criteria.not(
                'AND',
                [Criteria.equals('name', this.twintCustomFieldSetName)],
            ));
            criteria.addFilter(Criteria.equals('relations.entityName', 'order'));

            return criteria;
        }
    }
});

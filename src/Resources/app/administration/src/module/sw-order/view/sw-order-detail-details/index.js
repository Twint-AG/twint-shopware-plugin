const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-order-detail-details', {
    inject: [
        'repositoryFactory',
        'acl',
    ],
    data() {
        return {
            customFieldSets: []
        }
    },
    methods: {
        createdComponent() {
            this.$super('createdComponent');
            this.customFieldSetRepository.search(this.customFieldSetCriteria).then((result) => {
                this.customFieldSets = result;
            });
        }
    },
    computed: {
        customFieldSetRepository() {
            return this.repositoryFactory.create('custom_field_set');
        },

        customFieldSetCriteria() {
            const criteria = new Criteria(1, null);
            criteria.addFilter(Criteria.not(
                'AND',
                [Criteria.equals('name', 'twint_payment_custom_field_set')],
            ));
            criteria.addFilter(Criteria.equals('relations.entityName', 'order'));

            return criteria;
        }
    }
});

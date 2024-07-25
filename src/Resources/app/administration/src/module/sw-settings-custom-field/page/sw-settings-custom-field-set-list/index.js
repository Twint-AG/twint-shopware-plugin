const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-settings-custom-field-set-list', {
    inject: ['acl', 'feature'],
    data() {
        return {
            twintCustomFieldSetName: 'twint_payment_custom_field_set'
        }
    },
    computed: {

        listingCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            const params = this.getMainListingParams();

            criteria.addFilter(Criteria.multi(
                'OR',
                [
                    ...this.getLocaleCriterias(params.term),
                    ...this.getTermCriteria(params.term),
                ],
            ));

            criteria.addFilter(Criteria.equals('appId', null));
            criteria.addFilter(Criteria.not(
                'AND',
                [Criteria.equals('name', this.twintCustomFieldSetName)],
            ));
            return criteria;
        }
    }
});
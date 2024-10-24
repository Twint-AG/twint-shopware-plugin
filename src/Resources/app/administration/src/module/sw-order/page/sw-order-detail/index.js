import template from './sw-order-detail.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-order-detail', {
    template,
    data() {
        return {
            isLoading: false,
            pairings: null,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            naturalSorting: true
        }
    },
    methods: {
        createdComponent() {
            this.$super('createdComponent');
            this.isLoading = true;
            this.getPairingList();
            this.$root.$on('save-edits', this.onSaveEdits);
        },
        getPairingList() {
            this.naturalSorting = this.sortBy === 'createdAt';
            const criteria = new Criteria();
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));
            criteria.addAssociation('order');
            criteria.addFilter(Criteria.equals('order.id', this.orderId))

            this.isLoading = true;
            this.twintPairingRepository.search(criteria).then((pairings) => {
                this.pairings = pairings;
                this.isLoading = false;
            }).catch(() => {
                this.isLoading = false;
            });;
        },
        async onSaveEdits() {
            const result = await this.$super('onSaveEdits');
            return result;
        }
    },
    destroyed() {
        this.$root.$off('save-edits');
    },
    computed: {
        twintPairingRepository() {
            return this.repositoryFactory.create('twint_pairing');
        },
        isTwintOrder() {
            let transactions = this.order?.transactions ?? [];

            for (let transaction of transactions) {
                if (transaction.paymentMethod.handlerIdentifier.includes('Twint')) {
                    return true;
                }
            }

            return false;
        }
    }
});

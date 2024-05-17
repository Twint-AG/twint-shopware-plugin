import template from './transaction-log-modal.html.twig';
const { Mixin, Data: { Criteria } } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            isLoading: false,
            items: null,
            sortBy: 'createdAt',
            criteriaLimit: 500,
            criteriaPage: 1,
            limit: 500
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [
                {
                    dataIndex: 'url',
                    property: 'url',
                    label: 'payonePayment.notificationTarget.columns.url',
                    primary: true
                },
                {
                    dataIndex: 'isBasicAuth',
                    property: 'isBasicAuth',
                    label: 'payonePayment.notificationTarget.columns.isBasicAuth'
                },
                {
                    property: 'txactions',
                    label: 'payonePayment.notificationTarget.columns.txactions'
                },
            ];
        },
        repository() {
            return this.repositoryFactory.create('twint_transaction_log');
        },
        criteria() {
            const criteria = new Criteria(this.criteriaPage, this.criteriaLimit);

            return criteria;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        renderTxactions(content) {
            if(content === null || !content.length) {
                return '';
            }

            return content.join(", ");
        },

        createdComponent() {
            this.getList();
        },

        getList() {
            this.isLoading = true;

            const context = { ...Shopware.Context.api, inheritance: true };
            return this.repository.search(this.criteria, context).then((result) => {
                this.total = result.total;
                this.items = result;
                this.isLoading = false;
            });
        },

        onClose(){
            this.$emit('modal-close');
        }
    }
};

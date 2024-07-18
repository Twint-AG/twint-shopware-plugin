import template from './sw-order-detail-twint.html.twig';
import './sw-order-detail-twint.scss'

const {Application, Mixin} = Shopware;
const { Criteria } = Shopware.Data;

const {mapState, mapGetters} = Shopware.Component.getComponentHelper();

Shopware.Component.register('sw-order-detail-twint', {

    template,
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing')
    ],
    inject: ['repositoryFactory', 'acl', 'stateStyleDataProviderService'],

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    data() {
        return {
            isLoading: false,
            transactionLogs: null,
            refundedAmount: 0,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            naturalSorting: true,
            showTransactionLogDetailModal: false
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.getTransactionLogList();
            this.$root.$on('refund-finish', this.getTransactionLogList);
        },
        getTransactionLogList() {
            this.naturalSorting = this.sortBy === 'createdAt';
            const criteria = new Criteria();
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));
            criteria.addAssociation('order');
            criteria.addAssociation('paymentStateMachineState');
            criteria.addAssociation('orderStateMachineState');
            criteria.addFilter(Criteria.equals('orderId', this.orderId))

            this.isLoading = true;
            this.transactionLogRepository.search(criteria).then((transactionLogs) => {
                this.transactionLogs = transactionLogs;
                console.log(transactionLogs)
                window.a = transactionLogs
                this.isLoading = false;
            }).catch(() => {
                this.isLoading = false;
            });
        },
        /**
         * @param id
         */
        onOpenModalDetail(id) {
            this.showTransactionLogDetailModal = id;
        },
        onCloseModalDetail() {
            this.showTransactionLogDetailModal = false;
        },
        getVariantState(entity, state) {
            return this.stateStyleDataProviderService.getStyle(`${entity}.state`, state.technicalName).variant;
        }
    },
    destroyed() {
        this.$root.$off('refund-finish');
    },
    computed: {
        ...mapState('swOrderDetail', [
            'order',
            'versionContext',
            'orderAddressIds',
            'editing',
            'loading',
        ]),
        orderId() {
            return this.$route.params.id;
        },
        transactionLogRepository() {
            return this.repositoryFactory.create('twint_transaction_log');
        },
        /**
         *
         * @returns {*}
         */
        totalTransactionLogs() {
            return this.transactionLogs.length;
        },
        transactionLogColumns() {
            const app = Application.getApplicationRoot();

            if (!app) {
                return [];
            }

            return [{
                property: 'orderId',
                label: app.$tc('twint.order.transactionLog.list.columns.orderID'),
                allowResize: true,
            }, {
                property: 'apiMethod',
                label: app.$tc('twint.order.transactionLog.list.columns.apiMethod'),
                allowResize: true,
            }, {
                property: 'soapAction',
                label: app.$tc('twint.order.transactionLog.list.columns.soapAction'),
                allowResize: true,
            }, {
                property: 'paymentStateId',
                label: app.$tc('twint.order.transactionLog.list.columns.payment'),
                allowResize: true,
                sortable: false,
            }, {
                property: 'orderStateId',
                label: app.$tc('twint.order.transactionLog.list.columns.order'),
                allowResize: true,
                align: 'center',
            },{
                property: 'createdAt',
                label: app.$tc('twint.order.transactionLog.list.columns.createdAt'),
                allowResize: true,
            }];
        },
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    }
});

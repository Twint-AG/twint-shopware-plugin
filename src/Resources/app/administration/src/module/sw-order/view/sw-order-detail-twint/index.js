import template from './sw-order-detail-twint.html.twig';
import './sw-order-detail-twint.scss'
import '../../../../helper/version.mixin';

const {Application, Mixin} = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.register('sw-order-detail-twint', {

    template,
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('twint-version')
    ],
    inject: ['repositoryFactory', 'acl', 'stateStyleDataProviderService'],

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    data() {
        return {
            isLoading: true,
            order: null,
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
        this.fetchComponentData();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            if (this.isShopwareGte67) {
                Shopware.Utils.EventBus.on('refund-finish', this.getTransactionLogList);
            }
            else{
                this.$root.$on('refund-finish', this.getTransactionLogList);
            }
        },
        async fetchComponentData() {
            this.isLoading = true;
            try {
                // Fetch the main order and transaction logs in parallel for better performance
                await Promise.all([
                    this.fetchOrder(),
                    this.getTransactionLogList()
                ]);
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message || this.$tc('global.error-message'),
                });
            } finally {
                this.isLoading = false;
            }
        },

        async fetchOrder() {
            const criteria = new Criteria();
            criteria.addAssociation('currency');
            criteria.addAssociation('stateMachineState');
            criteria.addAssociation('transactions.stateMachineState');
            criteria.addAssociation('transactions.paymentMethod');
            criteria.addAssociation('deliveries.stateMachineState');
            this.order = await this.orderRepository.get(this.orderId, Shopware.Context.api, criteria);
        },
        async getTransactionLogList() {
            this.isLoading = true;
            this.naturalSorting = this.sortBy === 'createdAt';
            const criteria = new Criteria();
            criteria.setPage(1);
            criteria.setLimit(100);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));
            criteria.addAssociation('order');
            criteria.addAssociation('paymentStateMachineState');
            criteria.addAssociation('orderStateMachineState');
            criteria.addFilter(Criteria.equals('orderId', this.orderId))
            try {
                const transactionLogs = await this.transactionLogRepository.search(criteria);
                transactionLogs.forEach((transactionLog, index) => {
                    if (transactionLog.order === undefined) {
                        transactionLogs[index].order = {
                            'orderNumber' : ''
                        };
                    }
                    if (transactionLog.paymentStateMachineState === undefined) {
                        transactionLogs[index].paymentStateMachineState = {
                            'name' : ''
                        };
                    }
                    if (transactionLog.orderStateMachineState === undefined) {
                        transactionLogs[index].orderStateMachineState = {
                            'name' : ''
                        };
                    }
                });
                this.transactionLogs = transactionLogs;
                window.a = transactionLogs
                this.isLoading = false;
            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: error.message || this.$tc('global.error-message'),
                });
                // Ensure transactionLogs is an empty array on error to prevent template issues.
                this.transactionLogs = [];
            } finally {
                this.isLoading = false;
            }
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
            if (state && state.technicalName) {
                return this.stateStyleDataProviderService.getStyle(`${entity}.state`, state.technicalName).variant;
            }
            return null;
        }
    },
    destroyed() {
        if (this.isShopwareGte67) {
            Shopware.Utils.EventBus.off('refund-finish', this.getTransactionLogList);
        }
        else{
            this.$root.$off('refund-finish');
        }
    },
    computed: {
        orderId() {
            return this.$route.params.id;
        },
        orderRepository() {
            // A repository for fetching the main order
            return this.repositoryFactory.create('order');
        },
        transactionLogRepository() {
            return this.repositoryFactory.create('twint_transaction_log');
        },
        /**
         *
         * @returns {*}
         */
        totalTransactionLogs() {
            return this.transactionLogs ? this.transactionLogs.total : 0;
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
        }
    }
});

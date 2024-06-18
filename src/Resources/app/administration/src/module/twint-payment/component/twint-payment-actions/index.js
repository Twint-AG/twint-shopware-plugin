import template from './twint-payment-actions.html.twig';
import './twint-payment-actions.scss';

const { Component, Mixin } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;

Component.register('twint-payment-actions', {
    template,

    inject: [
        'repositoryFactory', 'acl', 'TwintPaymentService', 'orderStateMachineService'
    ],
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],
    props: {
        order: {
            type: Object,
            required: true,
        },
        orderId: {
            type: String,
            required: true,
        },
        totalAmount: {
            type: Number,
            required: true,
        },
        currency: {
            type: String,
            required: true,
        },
        decimalPrecision: {
            type: String,
            required: true,
        }
    },

    data() {
        return {
            reversalId: '',
            reversalHistory: [],
            refundAmount: 0,
            refundedAmount: 0,
            reason: '',
            refundableAmount: 0,
            isLoading: false,
            showRefundModal: false,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            naturalSorting: true,
            stateType: 'order_transaction',
            transaction: null,
            currentActionName: null,
            showStateModal: false,
        };
    },
    created() {
        this.createdComponent();
    },
    computed: {
        ...mapState('swOrderDetail', [
            'order',
            'versionContext',
            'orderAddressIds',
            'editing',
            'loading',
        ]),
        reversalHistoryRepository() {
            return this.repositoryFactory.create('twint_reversal_history');
        },
        paymentColumns() {
            return [{
                property: 'amount',
                label: this.$tc('twint.order.reversalHistory.columns.amount'),
                rawData: true,
            },{
                property: 'reason',
                label: this.$tc('twint.order.reversalHistory.columns.reason'),
                rawData: true,
            },{
                property: 'createdAt',
                label: this.$tc('twint.order.reversalHistory.columns.createdAt'),
                rawData: true,
            }];
        },
        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
        canRefund() {
            for (let i = 0; i < this.order.transactions.length; i += 1) {
                if (['paid', 'paid_partially', 'refunded_partially', 'refunded'].includes(this.order.transactions[i].stateMachineState.technicalName)) {
                    return true;
                }
            }
            return false;
        },
        currencyFilter() {
            return Shopware.Filter.getByName('currency');
        },
        dynamicStep() {
            if(this.decimalPrecision < 0) return 0.01;
            return Math.pow(10, -this.decimalPrecision);
        }
    },
    methods: {
        createdComponent() {
            this.getReversalHistoryList();
            for (let i = 0; i < this.order.transactions.length; i += 1) {
                if (!['cancelled', 'failed'].includes(this.order.transactions[i].stateMachineState.technicalName)) {
                    this.transaction = this.order.transactions[i];
                }
            }
            this.transaction = this.order.transactions.last();
        },
        showModal() {
            this.showRefundModal = true;
        },

        closeModal() {
            this.showRefundModal = '';
        },
        onCloseModal(){
            this.showRefundModal = '';
        },
        getReversalHistoryList() {
            this.naturalSorting = this.sortBy === 'createdAt';
            const criteria = new Criteria();
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));
            criteria.addFilter(Criteria.equals('order.id', this.orderId))
            criteria.addAggregation(Criteria.sum('refundedAmount', 'amount'))

            this.isLoading = true;
            this.reversalHistoryRepository.search(criteria).then((reversalHistory) => {
                this.reversalHistory = reversalHistory;
                const refundedAmount = parseFloat(reversalHistory?.aggregations?.refundedAmount?.sum);
                this.isLoading = false;
                this.refundableAmount = this.roundingFloat(this.totalAmount - refundedAmount, this.decimalPrecision);
            }).catch(() => {
                this.isLoading = false;
            });
        },
        refund() {
            const data = {
                'orderId' : this.orderId,
                'amount' : this.refundAmount,
                'reason': this.reason
            };
            this.isLoading = true;
            this.TwintPaymentService.refund(data).then((response) => {
                const success = response.success ?? false;
                this.currentActionName = response.action ?? '';
                if (success) {
                    this.showRefundModal = false;
                    this.getReversalHistoryList();
                    this.isLoading = false;
                    this.resetRefundForm();
                    this.$root.$emit('refund-finish');
                    if(this.currentActionName){
                        this.showStateModal = true;
                    }
                } else {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$tc('twint.refund.error.title'),
                        message: response.error
                    });
                }
            }).finally((errorResponse) => {
                this.isLoading = false;
                this.showRefundModal = false;
            });
        },
        resetRefundForm(){
            this.refundAmount = 0;
            this.reason = '';
        },
        roundingFloat(num, digits = 2) {
            return Number(Number(num).toFixed(digits));
        },
        onLeaveModalClose() {
            this.$root.$emit('refund-finish');
            this.currentActionName = null;
            this.showStateModal = false;
        },

        onLeaveModalConfirm(docIds, sendMail = true) {
            this.showStateModal = false;
            if(this.currentActionName){
                let transition = this.orderStateMachineService.transitionOrderTransactionState(
                    this.transaction.id,
                    this.currentActionName,
                    { documentIds: docIds, sendMail },
                );
                if (transition) {
                    transition.then(() => {
                        this.TwintPaymentService.orderStatus(this.orderId).then((response) => {
                            const success = response.success ?? false;
                            if(success) {
                                this.$root.$emit('save-edits');
                                this.$root.$emit('refund-finish');
                            }
                        });
                    }).catch((error) => {
                        this.createNotificationError({
                            title: this.$tc('twint.refund.error.title'),
                            message: error
                        });
                    }).finally(() => {
                        this.showStateModal = false;
                        this.currentActionName = null;
                    });
                }
            }
        }
    },
});

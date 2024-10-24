import template from './twint-payment-actions.html.twig';
import './twint-payment-actions.scss';

const { Component, Mixin } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;
const {ShopwareError} = Shopware.Classes;

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
            refundAmount: null,
            totalOrderAmount: 0,
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
            amountError: null,
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
        },
        amountError() {
            return this.amountError ? this.amountError : null;
        },
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
            this.totalOrderAmount = this.totalAmount;

            let self = this;
            this.TwintPaymentService.getPairing(this.order.id).then((response) => {
                if(response.pairing){
                    self.totalOrderAmount = response.pairing.amount;
                }
            });
        },
        showModal() {
            this.resetRefundForm();
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
                this.refundableAmount = this.roundingFloat(this.totalOrderAmount - refundedAmount, this.decimalPrecision);
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
                    this.updatePaymentStatus();
                } else {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$tc('twint.order.refund.error.title'),
                        message: response.error
                    });
                }
            }).finally((errorResponse) => {
                this.isLoading = false;
                this.showRefundModal = false;
            });
        },
        resetRefundForm(){
            this.refundAmount = null;
            this.amountError = null;
            this.reason = '';
        },
        roundingFloat(num, digits = 2) {
            return Number(Number(num).toFixed(digits));
        },

        updatePaymentStatus(docIds = [], sendMail = false) {
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
                            title: this.$tc('twint.order.refund.error.title'),
                            message: error
                        });
                    }).finally(() => {
                        this.currentActionName = null;
                    });
                }
            }
        },
        amountInputChanged(value) {
            this.refundAmount = value;
            if (this.refundAmount === null || isNaN(this.refundAmount) || this.refundAmount <= 0) {
                this.amountError = new ShopwareError({
                    'code': 'zero',
                    'detail': this.$tc('twint.order.refund.error.negativeAmount')
                });
            } else {
                const parts = this.refundAmount.toString().split('.');
                if(parts.length === 2) {
                    const wholeNumberLength = parts[0].replace('-', '').length;
                    const decimalLength = parts[1] ? parts[1].length : 0;
                    if (wholeNumberLength + decimalLength > 19 || decimalLength > 2) {
                        this.amountError = new ShopwareError({
                            'code': 'wrongFormat',
                            'detail': this.$tc('twint.order.refund.error.invalidMoneyFormat')
                        });
                    }
                }
                if(value > this.refundableAmount){
                    this.amountError = new ShopwareError({
                        'code': 'exceedAmount',
                        'detail': this.$tc('twint.order.refund.error.exceedAmount')
                    });
                }
                else{
                    this.amountError = null;
                }
            }
        },
    },
});

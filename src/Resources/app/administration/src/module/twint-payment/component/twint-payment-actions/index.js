import template from './twint-payment-actions.html.twig';
import './twint-payment-actions.scss';

const { Component, Mixin } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();
const { Criteria } = Shopware.Data;

Component.register('twint-payment-actions', {
    template,

    inject: [
        'repositoryFactory', 'acl', 'TwintPaymentService'
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
        }
    },
    methods: {
        createdComponent() {
            this.getReversalHistoryList();
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
            criteria.addAssociation('order');
            criteria.addFilter(Criteria.equals('order.id', this.orderId))

            this.isLoading = true;
            this.reversalHistoryRepository.search(criteria).then((reversalHistory) => {
                this.reversalHistory = reversalHistory;
                let refundedAmount = 0;
                reversalHistory.forEach((reversal) => {
                    const captureAmount = Number(reversal.amount);
                    refundedAmount += captureAmount;
                });
                this.isLoading = false;
                this.refundableAmount = this.totalAmount - refundedAmount;
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

                if (success) {
                    this.showRefundModal = false;
                    this.getReversalHistoryList();
                    this.isLoading = false;
                    this.resetRefundForm();
                    this.$root.$emit('refund-finish');
                    this.$emit('save-edits');
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
        }
    },
});

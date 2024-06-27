const orderPage = {
    //Latest order details
    orderNumber: ".order-table:nth-child(1) .order-table-header-order-number span",
    paymentStatus: ".order-table:nth-child(1) .order-table-header-order-table-body div:nth-child(2) span", //Should be "Paid"
    paymentMethod: ".order-table:nth-child(1) .order-table-header-order-table-body div:nth-child(3) span", //Should be "TWINT - Regular Checkout"
    shippingMethod: ".order-table:nth-child(1) .order-table-header-order-table-body div:nth-child(4) span", //Should be Standard/Express
    expandBtn: ".order-table:nth-child(1) button.order-hide-btn.collapsed",
    orderQuantity: ".order-table:nth-child(1) .line-item-quantity .d-flex",
    subTotalPrice: ".order-table:nth-child(1) .line-item-total-price-value",
    shippingCost: ".order-table:nth-child(1) .order-item-detail-summary dd:first-of-type",
    grandTotalPrice: ".order-table:nth-child(1) .order-item-detail-summary dd:last-of-type"
}
export default orderPage
const orderSuccessPage = {
    //Success message
    paymentSuccessMsg: "div.alert-content",
    orderNumber: ".finish-ordernumber",

    //Information section
    paymentMethod: ".finish-order-details p:nth-child(2)",
    shippingMethod: ".finish-order-details p:nth-child(3)",

    //Summary section
    totalPrice: "dd.checkout-aside-summary-value:nth-child(2)",
    shippingCost: "dd.checkout-aside-summary-value:nth-child(4)",
    netTotalPrice: "dd.summary-net",
    grandTotalPrice: "dd.checkout-aside-summary-total",
}
export default orderSuccessPage
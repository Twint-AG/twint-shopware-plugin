const checkoutPage = {
    confirmTOSCheckbox: ".checkout-confirm-tos-checkbox",
    //twintPaymentTitle: "p[title*=TWINT]",   //to get the radio button ussing parents('.payment-method-input')
    submitOrderBtn: "#confirmFormSubmit",
    paymentMethodRadioBtn: ".payment-method-radio",
    shippingMethodRadioBttn: ".shipping-method-input",

    //Summary section
    totalPrice: "dd.checkout-aside-summary-value:nth-child(2)",
    shippingCost: "dd.checkout-aside-summary-value:nth-child(4)",
    netTotalPrice: "dd.summary-net",
    grandTotalPrice: "dd.checkout-aside-summary-total",

    //Guest usser layout
    salutationDropdown: "select#personalSalutation",
    firstNameField: "input#personalFirstName",
    lastNameField: "input#personalLastName",
    emailField: "input#personalMail",
    streetAddressField: "input#billingAddressAddressStreet",
    cityField: "input#billingAddressAddressCity",
    countryDropdown: "select#billingAddressAddressCountry",
    countinueBtn: "button.btn-lg[type=submit]"
}
export default checkoutPage
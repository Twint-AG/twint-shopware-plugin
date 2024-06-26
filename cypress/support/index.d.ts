declare namespace Cypress {
    interface Chainable {
        login(username :string, password: string): void;
        changeCurrency(currencyTitle: string): void;
        searchProduct(keyword :string): void;
        addToCartFromPLP(keyword: string);
        verifyProductInCart(productName: string): void;
        selectPaymentMethod(paymentMethod: string): void;
        selectShippingMethod(shippingMethod: string): void;
        clearProductInCart();
    }
}
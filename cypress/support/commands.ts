// ***********************************************
// This example commands.ts shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
//
// declare global {
//   namespace Cypress {
//     interface Chainable {
//       login(email: string, password: string): Chainable<void>
//       drag(subject: string, options?: Partial<TypeOptions>): Chainable<Element>
//       dismiss(subject: string, options?: Partial<TypeOptions>): Chainable<Element>
//       visit(originalFn: CommandOriginalFn, url: string, options: Partial<VisitOptions>): Chainable<Element>
//     }
//   }
// }

import homePage from "../e2e/pages/homePage";
import loginPage from "../e2e/pages/loginPage";
import cartFlyout from "../e2e/pages/cartFlyout";
import qrPage from "../e2e/pages/qrPage";

export function getPriceString(priceString): string {
  const regex = new RegExp(/[^\d+|\.|\,]/,"g")
  return priceString.replace(regex, "")
}

//Buyer Login to the store
Cypress.Commands.add("login", (username, password) => {
  // Go to Login
  cy.get(homePage.accountMenuBtn).click()
  cy.get(homePage.accountMenuDropdown).should("be.visible")
  cy.get(homePage.loginBtn).click()
  cy.url().should('include', '/account/login')

  // Login
  cy.get(loginPage.emailField).clear().type(username)
  cy.get(loginPage.passwordField).clear().type(password)
  cy.get(loginPage.loginBtn).click()
  cy.url().should('include', '/account')
})

// Change currency
Cypress.Commands.add("changeCurrency", (currencyTitle) => {
  cy.get(homePage.changeCurrencyBtn).click()
  cy.get('.currencies-menu .dropdown-menu').should('be.visible')
  cy.get('.top-bar-nav .currencies-menu .dropdown-menu .dropdown-item[title =' + currencyTitle).click()
  cy.get('.header-cart-total').should('contain', currencyTitle)
})

// Search for products
Cypress.Commands.add("searchProduct", (keyword) => {
  cy.get(homePage.searchBox).click()
  cy.get(homePage.searchBox).clear().type(keyword)
  cy.get(homePage.searchBtn).click()
  cy.url().should('contain', '/search?search=' + keyword)
})

// Add Product to Cart from Product List Page
Cypress.Commands.add("addToCartFromPLP", (productName) => {
  cy.get(".product-name[title = " + '"' + productName + '"]').parent().find(".btn-buy").click()
    
  // Verify the product was added to cart in the cart flyout
  let existing = false
  cy.get(".offcanvas-cart-items a.line-item-label").each(($el, index, $list) => {
    if ($el.text().includes(productName)) {
      existing = true
      expect(existing).to.be.true
    }
  })
})

// Clear products in cart
Cypress.Commands.add("clearProductInCart", () => {
  cy.get(homePage.cartBtn).click()

  // Keep removing products in cart if existed
  cy.get(".offcanvas-cart").then(($cartBody) => {
    if ($cartBody.find(".line-item-remove-button").length) {
      cy.get(".js-cart-item").first().find(".line-item-remove-button").click()
    }
  })

  cy.get(".cart-offcanvas button.js-offcanvas-close").click() // Close the cart flyout
})

// Select Payment method
Cypress.Commands.add("selectPaymentMethod", (paymentMethod) => {
  cy.get(".payment-method-radio")
    .contains(paymentMethod)
    .parents().eq(1).find("input.payment-method-input").check()
    .should('be.checked')
})

// Select shipping method
Cypress.Commands.add("selectShippingMethod", (shippingMethod) => {
  cy.get(".shipping-method-description strong")
  .contains(shippingMethod)
  .parents().eq(2).find("input.shipping-method-input").check()
  .should('be.checked')
})

Cypress.Commands.overwrite('visit', (originalFn, url, options) => {
  const baseUrl = Cypress.env('BASE_URL');
  const authUrl = `${baseUrl}${url || ''}`;

  return originalFn(authUrl, options);
});


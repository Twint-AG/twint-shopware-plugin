import homePage from "../pages/homePage";
import checkoutPage from "../pages/checkoutPage";
import cartFlyout from "../pages/cartFlyout";
import qrPage from "../pages/qrPage";

// Import functions
import {getPriceString} from "../../support/commands";

const userData = require("../../fixtures/testAccount")

describe("ser perform Twint Regular Checkout", () => {
  beforeEach("Open the front store", () => {
    // Visite the store front
    // To do: manage this by env variable
    cy.visit('/', {
        auth: {
            username: userData.basicAuth.username,
            password: userData.basicAuth.password
        }
    })
    cy.get(homePage.storeLogo).should("be.visible")

    // Change currency to CHF
    cy.changeCurrency("CHF")

    // Clear the Cart if there is any item
    cy.clearProductInCart()
  })

  it('User should be able to pay for a product with Twint Regular Checkout', () => {    
    // User ogin
    cy.login(userData.testAccount.username, userData.testAccount.password)
    
    // Search for testing products
    cy.searchProduct("Test")

    // Add product to cart from product list page and go to checkout page
    cy.addToCartFromPLP("Test Product 1")

    // Click Go to checkout button
    cy.get(cartFlyout.goToCheckoutBtn).click()
    cy.url().should("includes", "/checkout/confirm")

    // On the Checkout page - Confirm TOS checkbox
    cy.get(checkoutPage.confirmTOSCheckbox).check()

    // Select Payment method
    cy.selectPaymentMethod("TWINT")

    // Select shipping method
    cy.selectShippingMethod("Express")

    // Get the grand total amount
    cy.get(checkoutPage.grandTotalPrice).invoke("text").then((expectedPrice) => {
      
      // Submit order
      cy.get(checkoutPage.submitOrderBtn).click()
      .url().should('contain', '/payment/waiting/') // User should be redirected to the QR page

      // The QR code and token should be visible
      cy.get(qrPage.qrCode).should("be.visible")
      cy.get(qrPage.qrToken).should("be.visible")

      // Verify the charging price
      cy.get(qrPage.priceAmount).invoke("text").then(($chargePrice) => {
        expect(getPriceString($chargePrice)).to.equal(getPriceString(expectedPrice))
      })
    })
  })

  it('User should be able to pay for two products with Twint Regular Checkout', () => {
    // User ogin
    cy.login(userData.testAccount.username, userData.testAccount.password)

    // Search for testing products
    cy.searchProduct("Test")

    // Add product to cart from product list page
    cy.addToCartFromPLP("Test Product 1")

    // CLose the Cart Flyout
    cy.get(cartFlyout.continueShoppingBtn).click().should("not.be.visible")

    // Add the second product to cart from the product list page
    cy.addToCartFromPLP("Test Product 2")

    // Click Go to checkout button
    cy.get(cartFlyout.goToCheckoutBtn).click()
    cy.url().should("includes", "/checkout/confirm")

    // On the Checkout page - Confirm TOS checkbox
    cy.get(checkoutPage.confirmTOSCheckbox).check()

    // Select Payment method
    cy.selectPaymentMethod("TWINT")

    // Select shipping method
    cy.selectShippingMethod("Express")

    // Get the grand total amount
    cy.get(checkoutPage.grandTotalPrice).invoke("text").then((expectedPrice) => {
      
      // Submit order
      cy.get(checkoutPage.submitOrderBtn).click()
      .url().should('contain', '/payment/waiting/') // User should be redirected to the QR page

      // The QR code and token should be visible
      cy.get(qrPage.qrCode).should("be.visible")
      cy.get(qrPage.qrToken).should("be.visible")

      // Verify the charging price
      cy.get(qrPage.priceAmount).invoke("text").then(($chargePrice) => {
        expect(getPriceString($chargePrice)).to.equal(getPriceString(expectedPrice))
      })
    })
  })

  it('Guess user should be able to pay for a product with Twint Regular Checkout', () => {
    // Search for testing products
    cy.searchProduct("Test")

    // Add product to cart from product list page and go to checkout page
    cy.addToCartFromPLP("Test Product 1")

    // Click Go to checkout button
    cy.get(cartFlyout.goToCheckoutBtn).click()
    cy.url().should("includes", "/checkout/register")

    // Guess user enter order info
    cy.get(checkoutPage.salutationDropdown)
      .select(userData.guessAccount.salutation)
      .should("contain",userData.guessAccount.salutation)
    cy.get(checkoutPage.firstNameField).type(userData.guessAccount.firstname)
    cy.get(checkoutPage.lastNameField).type(userData.guessAccount.lastname)
    cy.get(checkoutPage.emailField).type(userData.guessAccount.email)
    cy.get(checkoutPage.streetAddressField).type(userData.guessAccount.street)
    cy.get(checkoutPage.cityField).type(userData.guessAccount.city)
    cy.get(checkoutPage.countryDropdown).select(userData.guessAccount.country)
    cy.get("option").contains(userData.guessAccount.country).should("have.attr","selected", "selected")
    cy.get(checkoutPage.countinueBtn).click()

    // On the Checkout page - Confirm TOS checkbox
    cy.get(checkoutPage.confirmTOSCheckbox).check()

    // Select Payment method
    cy.selectPaymentMethod("TWINT")

    // Select shipping method
    cy.selectShippingMethod("Express")

    // Get the grand total amount
    cy.get(checkoutPage.grandTotalPrice).invoke("text").then((expectedPrice) => {
      
      // Submit order
      cy.get(checkoutPage.submitOrderBtn).click()
      .url().should('contain', '/payment/waiting/') // User should be redirected to the QR page

      // The QR code and token should be visible
      cy.get(qrPage.qrCode).should("be.visible")
      cy.get(qrPage.qrToken).should("be.visible")

      // Verify the charging price
      cy.get(qrPage.priceAmount).invoke("text").then(($chargePrice) => {
        expect(getPriceString($chargePrice)).to.equal(getPriceString(expectedPrice))
      })
    })
  })
})
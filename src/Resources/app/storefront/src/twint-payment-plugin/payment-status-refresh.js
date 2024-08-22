import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";
import DomAccess from 'src/helper/dom-access.helper';
import ExpressCheckoutButton from './express-checkout-button';
import AjaxOffCanvas from 'src/plugin/offcanvas/ajax-offcanvas.plugin';
import Iterator from 'src/helper/iterator.helper';

export default class PaymentStatusRefresh extends Plugin {
  static POLL_LIMIT = 500;

  static options = {
    containerSelector: '.twint-qr-container',
    pairingHash: null,
    interval: 1000,
    expressCheckout: false
  };

  count = 0;

  init() {
    this.checking = false;
    this.client = new HttpClient();

    if (this.options.expressCheckout) {
      this.checkExpressCheckoutStatus();
    } else {
      this.$container = DomAccess.querySelector(document, this.options.containerSelector);
      this.pairingId = this.$container.getAttribute('data-pairing-id');

      this.checkRegularCheckoutStatus();
    }

    this.modal = null;
    this.success = false;

    this.startedAt = null;
  }

  begin(){
    this.startedAt = new Date();
  }

  getDomain() {
    this.domain = "";
    if (window.hasOwnProperty('storefrontUrl')) {
      this.domain = window.storefrontUrl;
    }

    return this.domain;
  }

  reachLimit() {
    if (this.checking || this.count > PaymentStatusRefresh.POLL_LIMIT) {
      return true;
    }

    this.count++;
    this.checking = true;

    return false;
  }

  registerEvents() {
    if (!this.registered) {
      ExpressCheckoutButton.modal.setOnClosed(this.onModalClosed.bind(this));

      this.registered = true;
    }
  }

  onModalClosed(event) {
    if (this.isOnCartPage() && this.success) {
      window.location.reload();
    }
  }

  checkExpressCheckoutStatus() {
    if(!this.startedAt){
      this.begin();
    }

    this.registerEvents();

    if (this.reachLimit())
      return;

    let url = window.router['frontend.twint.monitoring'];
    url = url.replace('--hash--', this.options.pairingHash);

    this.client.get(url, (response, request) => {
      this.checking = false;

      if (request.status !== 200) {
        return this.onError(request);
      }

      const data = JSON.parse(response);

      if (data.completed) {
        this.startedAt = null;

        if (data.orderId) {
          this.onExpressPaid(data);
        } else
          ExpressCheckoutButton.modal.close();

      } else {
        let interval = this.getInterval();
        if(interval > 0) {
          setTimeout(this.checkExpressCheckoutStatus.bind(this), interval);
        }
      }
    });
  }

  isOnCartPage() {
    let cartRoute = window.router['frontend.checkout.cart.page'];

    return window.location.pathname.includes(cartRoute);
  }

  onExpressPaid(response) {
    this.success = true;

    const CartWidgetPluginInstances = window.PluginManager.getPluginInstances('CartWidget');
    Iterator.iterate(CartWidgetPluginInstances, instance => instance.fetch());
    this.$emitter.publish('fetchCartWidgets');
    AjaxOffCanvas.close();

    if(response.orderId && response['thank-you']){
      this.ThankYouPageLoaded(response['thank-you']);

      return;
    }

    this.loadThankYouPage(response['thank-you']);
  }

  loadThankYouPage() {
    let url = window.router['frontend.twint.express'];
    url = url.replace('--hash--', this.options.pairingHash);
    this.client.get(url, this.ThankYouPageLoaded.bind(this));
  }

  ThankYouPageLoaded(response) {
    ExpressCheckoutButton.modal.updateContent(response);
    let titleEl = DomAccess.querySelector(document, '.js-pseudo-modal .twint-modal .modal-title');
    titleEl.innerHTML = titleEl.getAttribute('data-finish');
  }

  checkRegularCheckoutStatus() {
    if (this.reachLimit())
      return;

    let url = window.router['frontend.twint.monitoring'];
    url = url.replace('--hash--', this.pairingId);

    this.client.get(url, (response) => {
      this.checking = false;
      try {
        const jsonResponse = JSON.parse(response);
        const completed = (typeof jsonResponse.completed === "boolean") ? jsonResponse.completed : false;
        if (completed) {
          this.startedAt = null;
          location.reload();
        } else {
          let interval = this.getInterval();
          if(interval > 0) {
            setTimeout(this.checkRegularCheckoutStatus.bind(this), interval);
          }
        }
      } catch (e) {
      }
    }, 'application/json', true);
  }

  onError(request) {
    ExpressCheckoutButton.modal.updateContent(request.responseText);
    let titleEl = DomAccess.querySelector(document, '.js-pseudo-modal .twint-modal .modal-title');
    titleEl.innerHTML = titleEl.getAttribute('data-finish');

    this.success = false;
    this.startedAt = null;
  }

  getInterval(){
    if(!this.startedAt){
      this.begin();
    }

    let now = new Date();
    const seconds = Math.floor((now - this.startedAt) / 1000);

    let currentInterval = 2000; // Default to the first interval

    // express
    let stages = {
      0: 2000,
      600: 10000, //10 mins
      1800: 0 // 30 mins
    }

    //regular
    if(!this.options.expressCheckout){
      stages ={
        0: 2000,
        300: 10000, //5 min
        1800: 0 // 30 mins
      }
    }

    for (const [second, interval] of Object.entries(stages)) {
      if (seconds >= parseInt(second)) {
        currentInterval = interval;
      } else {
        break;
      }
    }

    console.log("interval", this.startedAt, seconds, currentInterval);

    return currentInterval;
  }
}

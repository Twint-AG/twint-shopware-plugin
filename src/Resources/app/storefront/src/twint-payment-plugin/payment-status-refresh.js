import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";
import DomAccess from 'src/helper/dom-access.helper';
import ExpressCheckoutButton from './express-checkout-button';
import AjaxOffCanvas from 'src/plugin/offcanvas/ajax-offcanvas.plugin';

export default class PaymentStatusRefresh extends Plugin {

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
            this.orderNumber = this.$container.getAttribute('data-order-number');

            this.checkRegularCheckoutStatus();
        }
    }

    getDomain() {
        this.domain = "";
        if (window.hasOwnProperty('storefrontUrl')) {
            this.domain = window.storefrontUrl;
        }

        return this.domain;
    }

    reachLimit() {
        if (this.checking || this.count > 10) {
            return true;
        }

        this.count++;
        this.checking = true;

        return false;
    }

    checkExpressCheckoutStatus() {
        if (this.reachLimit())
            return;

        let url = window.router['frontend.twint.monitoring'];
        url = url.replace('--hash--', this.options.pairingHash);

        this.client.get(url, (response) => {
            const data = JSON.parse(response);
            this.checking = false;
            if (data.completed) {
                if(data.orderId){
                    AjaxOffCanvas.close();
                    this.loadThankYouPage();
                }
                else
                    ExpressCheckoutButton.modal.close();

            } else {
                setTimeout(this.checkExpressCheckoutStatus.bind(this), this.options.interval);
            }
        });
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

        let url = window.router['frontend.twint.order'];
        url = url.replace('--number--', this.orderNumber);

        this.client.get(url, (response) => {
            this.checking = false;
            try {
                const jsonResponse = JSON.parse(response);
                const reload = (typeof jsonResponse.reload === "boolean") ? jsonResponse.reload : false;
                if (reload) {
                    location.reload();
                } else {
                    setTimeout(this.checkRegularCheckoutStatus.bind(this), this.options.interval);
                }
            } catch (e) {
            }
        }, 'application/json', true);
    }
}

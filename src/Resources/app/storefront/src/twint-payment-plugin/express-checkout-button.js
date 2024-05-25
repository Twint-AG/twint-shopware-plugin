import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";

export default class ExpressCheckoutButton extends Plugin {

    static options = {
        formSelector: 'form',
        useCart:false
    };

    loadingPopup = null;

    _init() {
        this.checking = false;
        this.client = new HttpClient();
        if (!this.options.useCart) {
            this.form = this.el.closest(this.options.formSelector);
        }

        this._registerEvents();
    }

    getLineItems(){
        if(this.options.useCart) {
            return [];
        }

        // Create a FormData object from the form
        const formData = new FormData(this.form);
        const item = {};

        const lineItemsRegex = /lineItems\[[^\]]+\]\[([^\]]+)\]/;


        formData.forEach((value, key) => {
            if (key.startsWith('lineItems')) {
                const match = key.match(lineItemsRegex);
                if (match && match[1]) {
                    switch (match[1]) {
                        case 'stackable':
                        case 'removable':
                            value = value === '1';
                            break;

                        case 'quantity':
                            value = parseInt(value);
                            break;
                    }

                    item[match[1]] = value;
                }
            }
        });

        const lineItems = [];
        lineItems.push(item);

        return lineItems;
    }

    /**
     * Register events
     * @private
     */
    _registerEvents() {
        this.el.addEventListener('click', this.onClick.bind(this));
    }

    getLoadingPopup() {
        if(!this.loadingPopup) {
            this.loadingPopup = window.PluginManager.getPluginInstanceFromElement(document.querySelector('#twint-loading-popup'), 'TwintLoadingPopup');
        }

        return this.loadingPopup;
    }

    onClick(event) {
        if(this.checking) return ;
        this.checking = true;

        this.client.abort();
        this.getLoadingPopup().show();
        this.client.post('/twint/express-checkout', JSON.stringify({
            lineItems: this.getLineItems(),
            useCart: this.options.useCart
        }), this.onFinish.bind(this));

        event.stopPropagation();
        event.preventDefault();
        return false;
    }

    onFinish(responseText, request) {
        this.checking = false;
        this.getLoadingPopup().hide();
        if(request.status === 200) {
            const response = JSON.parse(responseText);
            window.location.href = this.baseUrl() + response.redirectUrl

            return;
        }

        this.onError(responseText);
    }

    onError(responseText) {
        console.log("Express checkout error: ", responseText);
    }

    baseUrl() {
        return window.location.protocol + '//' + window.location.host;
    }
}

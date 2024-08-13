import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";
import TwintModal from './modal';
import Iterator from 'src/helper/iterator.helper';

export default class ExpressCheckoutButton extends Plugin {

    static options = {
        formSelector: 'form',
        useCart:false
    };

    loadingPopup = null;
    static modal = null;

    init() {
        this.checking = false;
        this.client = new HttpClient();
        if (!this.options.useCart) {
            this.form = this.el.closest(this.options.formSelector);
        }

        if(!ExpressCheckoutButton.modal) {
            ExpressCheckoutButton.modal = new TwintModal(
                '',
                true,
                '.js-twint-modal-template',
                '.js-twint-modal-template-content-element',
                '.js-twint-modal-template-title-element'
            );
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
        this.client.post(window.router['frontend.twint.express-checkout'], JSON.stringify({
            lineItems: this.getLineItems(),
            useCart: this.options.useCart
        }), this.onFinish.bind(this));

        event.stopPropagation();
        event.preventDefault();
        return false;
    }

    onFinish(responseText, request) {
        this.getLoadingPopup().hide();
        if(request.status === 200) {
            const response = JSON.parse(responseText);
            if(response.hasOwnProperty('needAddProductToCart') && response.needAddProductToCart && this.options.useCart === false){
                this.onAddProductToCart();
            }
            else{
                this.onModalLoaded(response.content);
            }
            return;
        }

        this.onError(responseText);
    }

    onModalLoaded(responseText){
        this.checking = false;

        ExpressCheckoutButton.modal.open();
        ExpressCheckoutButton.modal.updateContent(responseText);

        window.PluginManager.initializePlugin('TwintPaymentStatusRefresh', '[data-twint-payment-status-refresh]');
        window.PluginManager.initializePlugin('TwintCopyToken', '[data-twint-copy-token]');
        window.PluginManager.initializePlugin('TwintAppSwitchHandler', '[data-app-selector]');
    }

    onAddProductToCart(){
        this.checking = false;
        const requestUrl = window.router['frontend.checkout.line-item.add'];
        let formData = new FormData();
        for (const lineItem of this.getLineItems()) {
            for(const [key, value] of Object.entries(lineItem)){
                formData.append('lineItems[' + lineItem.id + '][' + key + ']', value);
            }
        }
        formData.append('redirectTo', 'frontend.cart.offcanvas');
        const offCanvasCartInstances = PluginManager.getPluginInstances('OffCanvasCart');
        Iterator.iterate(offCanvasCartInstances, instance => {
            instance.openOffCanvas(requestUrl, formData, () => {
                this.$emitter.publish('openOffCanvasCart');
            });
        });
    }

    onError(responseText) {
        console.log("Express checkout error: ", responseText);
    }

}

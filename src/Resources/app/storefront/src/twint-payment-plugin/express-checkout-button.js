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

    getFormData() {
        const formData = (!this.options.useCart && this.form) ? new FormData(this.form) : new FormData();
        formData.set('useCart', this.options.useCart ? '1' : '0');

        return formData;
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
        event.stopPropagation();
        event.preventDefault();

        if(this.checking) return false;
        this.checking = true;

        this.client.abort();
        this.getLoadingPopup().show();
        this.client.post(
            window.router['frontend.twint.express-checkout'],
            this.getFormData(),
            this.onFinish.bind(this)
        );

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
        // Submit the buy form verbatim so nested fields (e.g. payload) are preserved.
        const formData = new FormData(this.form);
        formData.set('redirectTo', 'frontend.cart.offcanvas');
        const offCanvasCartInstances = PluginManager.getPluginInstances('OffCanvasCart');
        Iterator.iterate(offCanvasCartInstances, instance => {
            instance.openOffCanvas(requestUrl, formData, () => {
                this.$emitter.publish('openOffCanvasCart');
            });
        });
    }

    onError(responseText) {
        this.checking = false;
        console.log("Express checkout error: ", responseText);
    }

}

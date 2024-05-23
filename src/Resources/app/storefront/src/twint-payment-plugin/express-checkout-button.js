import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";

export default class ExpressCheckoutButton extends Plugin {

    static options = {
        formSelector: 'form'
    };

    _init() {
        this.checking = false;
        this.client = new HttpClient();
        this.form = this.el.closest(this.options.formSelector);

        this._registerEvents();
    }

    getLineItems(){
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
                            value = value == '1';
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

    onClick(event) {
        if(this.checking) return ;
        this.checking = true;

        this.client.abort();
        this.client.post('/twint/express-checkout', JSON.stringify(this.getLineItems()), this.onFinish.bind(this));

        event.stopPropagation();
        event.preventDefault();
        return false;
    }

    onFinish(responseText, request) {
        this.checking = false;
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

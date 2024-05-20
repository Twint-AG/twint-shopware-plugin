import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class AppSwitchHandler extends Plugin {

    static options = {
        pageSelector: '.qr-waiting-container',
        appSelector: '#logo-container',
        appLinkSelector: "#app-chooser",
        qrCodeSelector: ".qr-code-mobile",
        lastReloadTimeKey: "lastReloadTime",
        appCountDownInterval: 10, //second
        intervalCheck: 10, //second
    };

    init() {
        this.httpClient = new HttpClient();
        this.pageSelector = DomAccess.querySelector(document, this.options.pageSelector);
        this.appSelector = DomAccess.querySelector(document, this.options.appSelector);
        this.qrCodeSelector = DomAccess.querySelector(document, this.options.qrCodeSelector);
        this.appLinkSelector = DomAccess.querySelector(document, this.options.appLinkSelector);
        this.bankSelectors = DomAccess.querySelectorAll(this.appSelector, '.bank-logo', false);
        this.orderNumber = this.pageSelector.getAttribute('data-order-number');
        this.isMobile = this.pageSelector.getAttribute('data-mobile');
        this.isAndroidMobile = this.pageSelector.getAttribute('data-is-android-device');
        this.statusOrderEndpoint = '/payment/order/' + this.orderNumber;
        this._registerEvents();
    }
    /**
     * Register events
     * @private
     */
    _registerEvents() {
        if (this.bankSelectors) {
            this.bankSelectors.forEach((object) => {
                object.addEventListener('click', (event) => {
                    this.onClickBank(event, object);
                });
            });
        }
        if(this.appLinkSelector){
            this.appLinkSelector.addEventListener('change', this.onChangeAppList.bind(this))
        }
        var lastReloadTime = localStorage.getItem(this.options.lastReloadTimeKey);
        var currentTime = Date.now();
        var reloadInterval = this.options.intervalCheck * 1000;
        if (!lastReloadTime || (currentTime - lastReloadTime > reloadInterval)) {
            this.reloadPage(this);
        }
        this.reloadPageInterval = setInterval(() => {
            this.reloadPage(this);
        }, reloadInterval);

        if(this.isMobile && this.isAndroidMobile){
            let link = this.pageSelector.getAttribute('data-android-link');
            window.location.replace(link);
            const checkLocation = setInterval(() => {
                if (window.location.href !== link) {
                    this.showMobileQrCode();
                }
                clearInterval(checkLocation);
            }, this.options.appCountDownInterval * 1000);
        }
    }
    onClickBank(event, object) {
        event.preventDefault();
        var link = object.getAttribute('data-link');
        this.openAppBank(link);
    }
    onChangeAppList(event) {
        const select = event.target;
        let link = select.options[select.selectedIndex].value;
        this.openAppBank(link);
    }
    openAppBank(link) {
        if(link){
            window.location.replace(link);
            const checkLocation = setInterval(() => {
                if (window.location.href !== link) {
                    this.showMobileQrCode();
                }
                clearInterval(checkLocation);
            }, this.options.appCountDownInterval * 1000);
        }
    }
    reloadPage() {
        localStorage.setItem(this.options.lastReloadTimeKey, Date.now());
        this.httpClient.get(this.statusOrderEndpoint, (response) => {
            try {
                const jsonResponse = JSON.parse(response);
                const reload = (typeof jsonResponse.reload === "boolean") ? jsonResponse.reload : false;
                if(reload){
                    clearInterval(this.reloadPageInterval);
                    location.reload();
                }
            } catch (e) {}
        }, 'application/json', true);
    }
    showMobileQrCode() {
        this.qrCodeSelector.style['display'] = 'block';
    }
}

import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class AppSwitchHandler extends Plugin {

    static options = {
        pageSelector: '.twint-qr-container',
        appSelector: '#logo-container',
        appLinkSelector: "#app-chooser",
        qrCodeSelector: ".qr-code",
        appCountDownInterval: 2, //second
        appTimeoutInterval: 10 //second
    };

    init() {
        this.$container = DomAccess.querySelector(document, this.options.pageSelector);

        this.isMobile = this.$container.getAttribute('data-mobile') ?? false;
        this.isAndroid = this.$container.getAttribute('data-is-android-device') ?? false;
        this.isIos = this.$container.getAttribute('data-is-ios-device') ?? false;
        this.selectedApp = false;

        if (this.isIos) {
            this.handleIos();
        }

        if (this.isAndroid) {
            this.handleAndroid();
        }
    }

    handleIos() {
        this.$_apps = DomAccess.querySelector(document, this.options.appSelector);
        this.$qrCode = DomAccess.querySelectorAll(document, this.options.qrCodeSelector);
        this.$appLinks = DomAccess.querySelector(document, this.options.appLinkSelector);
        this.$banks = DomAccess.querySelectorAll(this.$_apps, '.bank-logo', false);


        if (this.$banks) {
            this.$banks.forEach((object) => {
                object.addEventListener('touchend', (event) => {
                    this.onClickBank(event, object);
                });
            });
        }

        if (this.$appLinks) {
            this.$appLinks.addEventListener('change', this.onChangeAppList.bind(this))
        }
        const checkTimeout = setInterval(() => {
            if (!this.selectedApp) {
                this.showMobileQrCode();
            }
            clearInterval(checkTimeout);
        }, this.options.appTimeoutInterval * 1000);
    }

    handleAndroid() {
        this.$qrCode = DomAccess.querySelectorAll(document, this.options.qrCodeSelector);

        let link = this.$container.getAttribute('data-android-link');
        window.location.replace(link);
        const checkLocation = setInterval(() => {
            this.showMobileQrCode();
            clearInterval(checkLocation);
        }, this.options.appCountDownInterval * 1000);
    }

    onClickBank(event, object) {
        var link = object.getAttribute('data-link');
        this.openAppBank(link);
    }

    onChangeAppList(event) {
        const select = event.target;
        let link = select.options[select.selectedIndex].value;
        this.openAppBank(link);
    }

    openAppBank(link) {
        if (link) {
            this.selectedApp = true;
            try {
                window.location.replace(link);

                const checkLocation = setInterval(() => {
                    if (window.location.href !== link) {
                        this.showMobileQrCode();
                    }
                    clearInterval(checkLocation);
                }, this.options.appCountDownInterval * 1000);
            } catch (e) {
                this.showMobileQrCode();
            }
        }
    }

    showMobileQrCode() {
        this.$qrCode.forEach((object) => {
            object.classList.remove('d-none');
        });
    }
}

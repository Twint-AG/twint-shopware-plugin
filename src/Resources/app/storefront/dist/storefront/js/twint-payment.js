"use strict";(self.webpackChunk=self.webpackChunk||[]).push([["twint-payment"],{4529:(e,t,o)=>{var i,r,n,a=o(6285),l=o(3206),s=o(8254);o(4690);class p extends a.Z{init(){this.httpClient=new s.Z,this.pageSelector=l.Z.querySelector(document,this.options.pageSelector),this.appSelector=l.Z.querySelector(document,this.options.appSelector),this.qrCodeSelector=l.Z.querySelector(document,this.options.qrCodeSelector),this.appLinkSelector=l.Z.querySelector(document,this.options.appLinkSelector),this.bankSelectors=l.Z.querySelectorAll(this.appSelector,".bank-logo",!1),this.orderNumber=this.pageSelector.getAttribute("data-order-number"),this.isMobile=this.pageSelector.getAttribute("data-mobile"),this.isAndroidMobile=this.pageSelector.getAttribute("data-is-android-device"),this.statusOrderEndpoint="/payment/order/"+this.orderNumber,this._registerEvents()}_registerEvents(){this.bankSelectors&&this.bankSelectors.forEach((e=>{e.addEventListener("touchend",(t=>{this.onClickBank(t,e)}))})),this.appLinkSelector&&this.appLinkSelector.addEventListener("change",this.onChangeAppList.bind(this));var e=localStorage.getItem(this.options.lastReloadTimeKey),t=Date.now(),o=1e3*this.options.intervalCheck;if((!e||t-e>o)&&this.reloadPage(this),this.reloadPageInterval=setInterval((()=>{this.reloadPage(this)}),o),this.isMobile&&this.isAndroidMobile){let e=this.pageSelector.getAttribute("data-android-link");window.location.replace(e);const t=setInterval((()=>{window.location.href!==e&&this.showMobileQrCode(),clearInterval(t)}),1e3*this.options.appCountDownInterval)}}onClickBank(e,t){var o=t.getAttribute("data-link");this.openAppBank(o)}onChangeAppList(e){const t=e.target;let o=t.options[t.selectedIndex].value;this.openAppBank(o)}openAppBank(e){if(e){window.location.replace(e);const t=setInterval((()=>{window.location.href!==e&&this.showMobileQrCode(),clearInterval(t)}),1e3*this.options.appCountDownInterval)}}reloadPage(){localStorage.setItem(this.options.lastReloadTimeKey,Date.now()),this.httpClient.get(this.statusOrderEndpoint,(e=>{try{const t=JSON.parse(e);"boolean"==typeof t.reload&&t.reload&&(clearInterval(this.reloadPageInterval),location.reload())}catch(e){}}),"application/json",!0)}showMobileQrCode(){this.qrCodeSelector.style.display="block"}}i=p,n={pageSelector:".qr-waiting-container",appSelector:"#logo-container",appLinkSelector:"#app-chooser",qrCodeSelector:".qr-code-mobile",lastReloadTimeKey:"lastReloadTime",appCountDownInterval:10,intervalCheck:10},(r=function(e){var t=function(e,t){if("object"!=typeof e||null===e)return e;var o=e[Symbol.toPrimitive];if(void 0!==o){var i=o.call(e,t||"default");if("object"!=typeof i)return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===t?String:Number)(e)}(e,"string");return"symbol"==typeof t?t:String(t)}(r="options"))in i?Object.defineProperty(i,r,{value:n,enumerable:!0,configurable:!0,writable:!0}):i[r]=n;window.PluginManager.register("AppSwitchHandler",p,"[data-app-selector]")}},e=>{e.O(0,["vendor-node","vendor-shared"],(()=>{return t=4529,e(e.s=t);var t}));e.O()}]);
(self.webpackChunk=self.webpackChunk||[]).push([["twint-payment"],{2615:(t,e,n)=>{"use strict";n.d(e,{Z:()=>a});var i=n(3637),o=n(8254),r=n(7906);let s=null;class a extends i.Z{static open(t=!1,e=!1,n=null,o="left",r=!0,s=i.Z.REMOVE_OFF_CANVAS_DELAY(),a=!1,c=""){if(!t)throw new Error("A url must be given!");i.r._removeExistingOffCanvas();const l=i.r._createOffCanvas(o,a,c,r);this.setContent(t,e,n,r,s),i.r._openOffcanvas(l)}static setContent(t,e,n,i,c){const l=new o.Z;super.setContent(`<div class="offcanvas-body">${r.Z.getTemplate()}</div>`,i,c),s&&s.abort();const u=t=>{super.setContent(t,i,c),"function"==typeof n&&n(t)};s=e?l.post(t,e,a.executeCallback.bind(this,u)):l.get(t,a.executeCallback.bind(this,u))}static executeCallback(t,e){"function"==typeof t&&t(e),window.PluginManager.initializePlugins()}}},3637:(t,e,n)=>{"use strict";n.d(e,{Z:()=>u,r:()=>l});var i=n(9658),o=n(2005),r=n(1966);const s="offcanvas",a=350;class c{constructor(){this.$emitter=new o.Z}open(t,e,n,i,o,r,s){this._removeExistingOffCanvas();const a=this._createOffCanvas(n,r,s,i);this.setContent(t,i,o),this._openOffcanvas(a,e)}setContent(t,e,n){const i=this.getOffCanvas();i[0]&&(i[0].innerHTML=t,this._registerEvents(n))}setAdditionalClassName(t){this.getOffCanvas()[0].classList.add(t)}getOffCanvas(){return document.querySelectorAll(`.${s}`)}close(t){const e=this.getOffCanvas();r.Z.iterate(e,(t=>{bootstrap.Offcanvas.getInstance(t).hide()})),setTimeout((()=>{this.$emitter.publish("onCloseOffcanvas",{offCanvasContent:e})}),t)}goBackInHistory(){window.history.back()}exists(){return this.getOffCanvas().length>0}_openOffcanvas(t,e){c.bsOffcanvas.show(),window.history.pushState("offcanvas-open",""),"function"==typeof e&&e()}_registerEvents(t){const e=i.Z.isTouchDevice()?"touchend":"click",n=this.getOffCanvas();r.Z.iterate(n,(e=>{const i=()=>{setTimeout((()=>{e.remove(),this.$emitter.publish("onCloseOffcanvas",{offCanvasContent:n})}),t),e.removeEventListener("hide.bs.offcanvas",i)};e.addEventListener("hide.bs.offcanvas",i)})),window.addEventListener("popstate",this.close.bind(this,t),{once:!0});const o=document.querySelectorAll(".js-offcanvas-close");r.Z.iterate(o,(n=>n.addEventListener(e,this.close.bind(this,t))))}_removeExistingOffCanvas(){c.bsOffcanvas=null;const t=this.getOffCanvas();return r.Z.iterate(t,(t=>t.remove()))}_getPositionClass(t){return"left"===t?"offcanvas-start":"right"===t?"offcanvas-end":`offcanvas-${t}`}_createOffCanvas(t,e,n,i){const o=document.createElement("div");if(o.classList.add(s),o.classList.add(this._getPositionClass(t)),!0===e&&o.classList.add("is-fullwidth"),n){const t=typeof n;if("string"===t)o.classList.add(n);else{if(!Array.isArray(n))throw new Error(`The type "${t}" is not supported. Please pass an array or a string.`);n.forEach((t=>{o.classList.add(t)}))}}return document.body.appendChild(o),c.bsOffcanvas=new bootstrap.Offcanvas(o,{backdrop:!1!==i||"static"}),o}}const l=Object.freeze(new c);class u{static open(t,e=null,n="left",i=!0,o=350,r=!1,s=""){l.open(t,e,n,i,o,r,s)}static setContent(t,e=!0,n=350){l.setContent(t,e,n)}static setAdditionalClassName(t){l.setAdditionalClassName(t)}static close(t=350){l.close(t)}static exists(){return l.exists()}static getOffCanvas(){return l.getOffCanvas()}static REMOVE_OFF_CANVAS_DELAY(){return a}}},7260:t=>{t.exports=function t(e,n,i){function o(s,a){if(!n[s]){if(!e[s]){if(r)return r(s,!0);var c=new Error("Cannot find module '"+s+"'");throw c.code="MODULE_NOT_FOUND",c}var l=n[s]={exports:{}};e[s][0].call(l.exports,(function(t){var n=e[s][1][t];return o(n||t)}),l,l.exports,t,e,n,i)}return n[s].exports}for(var r=void 0,s=0;s<i.length;s++)o(i[s]);return o}({1:[function(t,e,n){var i=t("closest"),o=t("component-event"),r=["focus","blur"];n.bind=function(t,e,n,s,a){return-1!==r.indexOf(n)&&(a=!0),o.bind(t,n,(function(n){var o=n.target||n.srcElement;n.delegateTarget=i(o,e,!0,t),n.delegateTarget&&s.call(t,n)}),a)},n.unbind=function(t,e,n,i){-1!==r.indexOf(e)&&(i=!0),o.unbind(t,e,n,i)}},{closest:2,"component-event":4}],2:[function(t,e,n){var i=t("matches-selector");e.exports=function(t,e,n){for(var o=n?t:t.parentNode;o&&o!==document;){if(i(o,e))return o;o=o.parentNode}}},{"matches-selector":3}],3:[function(t,e,n){var i=Element.prototype,o=i.matchesSelector||i.webkitMatchesSelector||i.mozMatchesSelector||i.msMatchesSelector||i.oMatchesSelector;function r(t,e){if(o)return o.call(t,e);for(var n=t.parentNode.querySelectorAll(e),i=0;i<n.length;++i)if(n[i]==t)return!0;return!1}e.exports=r},{}],4:[function(t,e,n){var i=window.addEventListener?"addEventListener":"attachEvent",o=window.removeEventListener?"removeEventListener":"detachEvent",r="addEventListener"!==i?"on":"";n.bind=function(t,e,n,o){return t[i](r+e,n,o||!1),n},n.unbind=function(t,e,n,i){return t[o](r+e,n,i||!1),n}},{}],5:[function(t,e,n){function i(){}i.prototype={on:function(t,e,n){var i=this.e||(this.e={});return(i[t]||(i[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){var i=this,o=function(){i.off(t,o),e.apply(n,arguments)};return this.on(t,o,n)},emit:function(t){for(var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),i=0,o=n.length;i<o;i++)n[i].fn.apply(n[i].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),i=n[t],o=[];if(i&&e)for(var r=0,s=i.length;r<s;r++)i[r].fn!==e&&o.push(i[r]);return o.length?n[t]=o:delete n[t],this}},e.exports=i},{}],6:[function(t,e,n){"use strict";n.__esModule=!0;var i=function(){function t(t,e){for(var n=0;n<e.length;n++){var i=e[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}}return function(e,n,i){return n&&t(e.prototype,n),i&&t(e,i),e}}();function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var r=function(){function t(e){o(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function(){var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=t.action,this.emitter=t.emitter,this.target=t.target,this.text=t.text,this.trigger=t.trigger,this.selectedText=""},t.prototype.initSelection=function(){if(this.text&&this.target)throw new Error('Multiple attributes declared, use either "target" or "text"');if(this.text)this.selectFake();else{if(!this.target)throw new Error('Missing required attributes, use either "target" or "text"');this.selectTarget()}},t.prototype.selectFake=function(){var t=this;this.removeFake(),this.fakeHandler=document.body.addEventListener("click",(function(){return t.removeFake()})),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.position="absolute",this.fakeElem.style.left="-9999px",this.fakeElem.style.top=document.body.scrollTop+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,this.selectedText=this.text,document.body.appendChild(this.fakeElem),this.fakeElem.select(),this.copyText()},t.prototype.removeFake=function(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function(){if("INPUT"===this.target.nodeName||"TEXTAREA"===this.target.nodeName)this.target.select(),this.selectedText=this.target.value;else{var t=document.createRange(),e=window.getSelection();t.selectNodeContents(this.target),e.addRange(t),this.selectedText=e.toString()}this.copyText()},t.prototype.copyText=function(){var t=void 0;try{t=document.execCommand(this.action)}catch(e){t=!1}this.handleResult(t)},t.prototype.handleResult=function(t){t?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function(){this.removeFake()},i(t,[{key:"action",set:function(){var t=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=t,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function(){return this._action}},{key:"target",set:function(t){if(void 0!==t){if(!t||"object"!=typeof t||1!==t.nodeType)throw new Error('Invalid "target" value, use a valid Element');this._target=t}},get:function(){return this._target}}]),t}();n.default=r,e.exports=n.default},{}],7:[function(t,e,n){"use strict";function i(t){return t&&t.__esModule?t:{default:t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function r(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}n.__esModule=!0;var s=i(t("./clipboard-action")),a=i(t("delegate-events")),c=function(t){function e(n,i){o(this,e),t.call(this),this.resolveOptions(i),this.delegateClick(n)}return r(e,t),e.prototype.resolveOptions=function(){var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof t.action?t.action:this.defaultAction,this.target="function"==typeof t.target?t.target:this.defaultTarget,this.text="function"==typeof t.text?t.text:this.defaultText},e.prototype.delegateClick=function(t){var e=this;this.binding=a.default.bind(document.body,t,"click",(function(t){return e.onClick(t)}))},e.prototype.undelegateClick=function(){a.default.unbind(document.body,"click",this.binding)},e.prototype.onClick=function(t){this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new s.default({action:this.action(t.delegateTarget),target:this.target(t.delegateTarget),text:this.text(t.delegateTarget),trigger:t.delegateTarget,emitter:this})},e.prototype.defaultAction=function(t){return l("action",t)},e.prototype.defaultTarget=function(t){var e=l("target",t);if(e)return document.querySelector(e)},e.prototype.defaultText=function(t){return l("text",t)},e.prototype.destroy=function(){this.undelegateClick(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(i(t("tiny-emitter")).default);function l(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}n.default=c,e.exports=n.default},{"./clipboard-action":6,"delegate-events":1,"tiny-emitter":5}]},{},[7])(7)},6633:(t,e,n)=>{"use strict";var i,o,r,s=n(6285),a=n(3206);class c extends s.Z{init(){var t,e,n;this.$container=a.Z.querySelector(document,this.options.pageSelector),this.isMobile=null!==(t=this.$container.getAttribute("data-mobile"))&&void 0!==t&&t,this.isAndroid=null!==(e=this.$container.getAttribute("data-is-android-device"))&&void 0!==e&&e,this.isIos=null!==(n=this.$container.getAttribute("data-is-ios-device"))&&void 0!==n&&n,this.isIos&&this.handleIos(),this.isAndroid&&this.handleAndroid()}handleIos(){this.$_apps=a.Z.querySelector(document,this.options.appSelector),this.$qrCode=a.Z.querySelectorAll(document,this.options.qrCodeSelector),this.$appLinks=a.Z.querySelector(document,this.options.appLinkSelector),this.$banks=a.Z.querySelectorAll(this.$_apps,".bank-logo",!1),this.$banks&&this.$banks.forEach((t=>{t.addEventListener("touchend",(e=>{this.onClickBank(e,t)}))})),this.$appLinks&&this.$appLinks.addEventListener("change",this.onChangeAppList.bind(this))}handleAndroid(){this.$qrCode=a.Z.querySelectorAll(document,this.options.qrCodeSelector);let t=this.$container.getAttribute("data-android-link");window.location.replace(t);const e=setInterval((()=>{this.showMobileQrCode(),clearInterval(e)}),1e3*this.options.appCountDownInterval)}onClickBank(t,e){var n=e.getAttribute("data-link");this.openAppBank(n)}onChangeAppList(t){const e=t.target;let n=e.options[e.selectedIndex].value;this.openAppBank(n)}openAppBank(t){if(t)try{window.location.replace(t);const e=setInterval((()=>{window.location.href!==t&&this.showMobileQrCode(),clearInterval(e)}),1e3*this.options.appCountDownInterval)}catch(t){this.showMobileQrCode()}}showMobileQrCode(){this.$qrCode.forEach((t=>{t.classList.remove("d-none")}))}}i=c,r={pageSelector:".twint-qr-container",appSelector:"#logo-container",appLinkSelector:"#app-chooser",qrCodeSelector:".qr-code",appCountDownInterval:2},(o=function(t){var e=function(t,e){if("object"!=typeof t||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var i=n.call(t,e||"default");if("object"!=typeof i)return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==typeof e?e:String(e)}(o="options"))in i?Object.defineProperty(i,o,{value:r,enumerable:!0,configurable:!0,writable:!0}):i[o]=r;var l=n(8254),u=n(378),h=n(1966);function d(t,e,n){return(e=function(t){var e=function(t,e){if("object"!=typeof t||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var i=n.call(t,e||"default");if("object"!=typeof i)return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==typeof e?e:String(e)}(e))in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}class p extends s.Z{constructor(...t){super(...t),d(this,"loadingPopup",null)}init(){this.checking=!1,this.client=new l.Z,this.options.useCart||(this.form=this.el.closest(this.options.formSelector)),p.modal||(p.modal=new u.Z("",!0,".js-twint-modal-template",".js-twint-modal-template-content-element",".js-twint-modal-template-title-element")),this._registerEvents()}getLineItems(){if(this.options.useCart)return[];const t=new FormData(this.form),e={},n=/lineItems\[[^\]]+\]\[([^\]]+)\]/;t.forEach(((t,i)=>{if(i.startsWith("lineItems")){const o=i.match(n);if(o&&o[1]){switch(o[1]){case"stackable":case"removable":t="1"===t;break;case"quantity":t=parseInt(t)}e[o[1]]=t}}}));const i=[];return i.push(e),i}_registerEvents(){this.el.addEventListener("click",this.onClick.bind(this))}getLoadingPopup(){return this.loadingPopup||(this.loadingPopup=window.PluginManager.getPluginInstanceFromElement(document.querySelector("#twint-loading-popup"),"TwintLoadingPopup")),this.loadingPopup}onClick(t){if(!this.checking)return this.checking=!0,this.client.abort(),this.getLoadingPopup().show(),this.client.post(window.router["frontend.twint.express-checkout"],JSON.stringify({lineItems:this.getLineItems(),useCart:this.options.useCart}),this.onFinish.bind(this)),t.stopPropagation(),t.preventDefault(),!1}onFinish(t,e){if(this.getLoadingPopup().hide(),200!==e.status)this.onError(t);else{const e=JSON.parse(t);e.hasOwnProperty("needAddProductToCart")&&e.needAddProductToCart&&!1===this.options.useCart?this.onAddProductToCart():this.onModalLoaded(e.content)}}onModalLoaded(t){this.checking=!1,p.modal.open(),p.modal.updateContent(t),window.PluginManager.initializePlugin("TwintPaymentStatusRefresh","[data-twint-payment-status-refresh]"),window.PluginManager.initializePlugin("TwintCopyToken","[data-twint-copy-token]"),window.PluginManager.initializePlugin("TwintAppSwitchHandler","[data-app-selector]")}onAddProductToCart(){this.checking=!1;const t=window.router["frontend.checkout.line-item.add"];let e=new FormData;for(const t of this.getLineItems())for(const[n,i]of Object.entries(t))e.append("lineItems["+t.id+"]["+n+"]",i);e.append("redirectTo","frontend.cart.offcanvas");const n=PluginManager.getPluginInstances("OffCanvasCart");h.Z.iterate(n,(n=>{n.openOffCanvas(t,e,(()=>{this.$emitter.publish("openOffCanvasCart")}))}))}onError(t){console.log("Express checkout error: ",t)}}d(p,"options",{formSelector:"form",useCart:!1}),d(p,"modal",null);var f=n(2615);function g(t,e,n){return(e=function(t){var e=function(t,e){if("object"!=typeof t||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var i=n.call(t,e||"default");if("object"!=typeof i)return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==typeof e?e:String(e)}(e))in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}class v extends s.Z{constructor(...t){super(...t),g(this,"count",0)}init(){this.checking=!1,this.client=new l.Z,this.options.expressCheckout?this.checkExpressCheckoutStatus():(this.$container=a.Z.querySelector(document,this.options.containerSelector),this.orderNumber=this.$container.getAttribute("data-order-number"),this.checkRegularCheckoutStatus())}getDomain(){return this.domain="",window.hasOwnProperty("storefrontUrl")&&(this.domain=window.storefrontUrl),this.domain}reachLimit(){return!!(this.checking||this.count>10)||(this.count++,this.checking=!0,!1)}checkExpressCheckoutStatus(){if(this.reachLimit())return;let t=window.router["frontend.twint.monitoring"];t=t.replace("--hash--",this.options.pairingHash),this.client.get(t,(t=>{const e=JSON.parse(t);this.checking=!1,e.completed?e.orderId?(f.Z.close(),this.loadThankYouPage()):p.modal.close():setTimeout(this.checkExpressCheckoutStatus.bind(this),this.options.interval)}))}loadThankYouPage(){let t=window.router["frontend.twint.express"];t=t.replace("--hash--",this.options.pairingHash),this.client.get(t,this.ThankYouPageLoaded.bind(this))}ThankYouPageLoaded(t){p.modal.updateContent(t);let e=a.Z.querySelector(document,".js-pseudo-modal .twint-modal .modal-title");e.innerHTML=e.getAttribute("data-finish")}checkRegularCheckoutStatus(){if(this.reachLimit())return;let t=window.router["frontend.twint.order"];t=t.replace("--number--",this.orderNumber),this.client.get(t,(t=>{this.checking=!1;try{const e=JSON.parse(t);"boolean"==typeof e.reload&&e.reload?location.reload():setTimeout(this.checkRegularCheckoutStatus.bind(this),this.options.interval)}catch(t){}}),"application/json",!0)}}function m(t,e,n){return(e=function(t){var e=function(t,e){if("object"!=typeof t||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var i=n.call(t,e||"default");if("object"!=typeof i)return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==typeof e?e:String(e)}(e))in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}g(v,"options",{containerSelector:".twint-qr-container",pairingHash:null,interval:1e3,expressCheckout:!1});class b extends s.Z{constructor(...t){super(...t),m(this,"active",!1)}init(){this.checking=!1,this.el=a.Z.querySelector(document,"#twint-loading-popup")}show(){this.active=!0,this.el.classList.add("active")}hide(){this.active=!1,this.el.classList.remove("active")}}var y=n(7260),w=n.n(y);function k(t,e,n){return(e=function(t){var e=function(t,e){if("object"!=typeof t||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var i=n.call(t,e||"default");if("object"!=typeof i)return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==typeof e?e:String(e)}(e))in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}class C extends s.Z{constructor(...t){super(...t),k(this,"clipboard",null)}init(){this.input=a.Z.querySelector(this.el,this.options.target),this.button=a.Z.querySelector(this.el,this.options.selector),this.button.addEventListener("click",this.onClick.bind(this)),this.clipboard=new(w())(this.options.selector),this.clipboard.on("success",this.onCopied.bind(this)),this.clipboard.on("error",this.onError.bind(this))}onClick(t){t.preventDefault(),this.input.disabled=!1}onCopied(t){t.clearSelection(),this.button.innerHTML="Copied!",this.button.classList.add("copied"),this.input.disabled=!0}onError(t){console.error("Action:",t.action),console.error("Trigger:",t.trigger)}}k(C,"options",{selector:"#btn-copy-token",target:"#qr-token"});const E=window.PluginManager;E.register("TwintAppSwitchHandler",c,"[data-app-selector]"),E.register("TwintExpressCheckoutButton",p,"[data-twint-express-checkout-button]"),E.register("TwintPaymentStatusRefresh",v,"[data-twint-payment-status-refresh]"),E.register("TwintLoadingPopup",b,"[data-twint-loading-popup]"),E.register("TwintCopyToken",C,"[data-twint-copy-token]")}},t=>{t.O(0,["vendor-node","vendor-shared"],(()=>{return e=6633,t(t.s=e);var e}));t.O()}]);
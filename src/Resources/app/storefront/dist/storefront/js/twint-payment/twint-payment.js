(()=>{var t={857:t=>{"use strict";var e=function(t){var e;return!!t&&"object"==typeof t&&"[object RegExp]"!==(e=Object.prototype.toString.call(t))&&"[object Date]"!==e&&t.$$typeof!==i},i="function"==typeof Symbol&&Symbol.for?Symbol.for("react.element"):60103;function n(t,e){return!1!==e.clone&&e.isMergeableObject(t)?a(Array.isArray(t)?[]:{},t,e):t}function r(t,e,i){return t.concat(e).map(function(t){return n(t,i)})}function o(t){return Object.keys(t).concat(Object.getOwnPropertySymbols?Object.getOwnPropertySymbols(t).filter(function(e){return Object.propertyIsEnumerable.call(t,e)}):[])}function s(t,e){try{return e in t}catch(t){return!1}}function a(t,i,l){(l=l||{}).arrayMerge=l.arrayMerge||r,l.isMergeableObject=l.isMergeableObject||e,l.cloneUnlessOtherwiseSpecified=n;var c,h,u=Array.isArray(i);return u!==Array.isArray(t)?n(i,l):u?l.arrayMerge(t,i,l):(h={},(c=l).isMergeableObject(t)&&o(t).forEach(function(e){h[e]=n(t[e],c)}),o(i).forEach(function(e){(!s(t,e)||Object.hasOwnProperty.call(t,e)&&Object.propertyIsEnumerable.call(t,e))&&(s(t,e)&&c.isMergeableObject(i[e])?h[e]=(function(t,e){if(!e.customMerge)return a;var i=e.customMerge(t);return"function"==typeof i?i:a})(e,c)(t[e],i[e],c):h[e]=n(i[e],c))}),h)}a.all=function(t,e){if(!Array.isArray(t))throw Error("first argument should be an array");return t.reduce(function(t,i){return a(t,i,e)},{})},t.exports=a},158:t=>{(function(e){t.exports=e()})(function(){return(function t(e,i,n){function r(s,a){if(!i[s]){if(!e[s]){if(o)return o(s,!0);var l=Error("Cannot find module '"+s+"'");throw l.code="MODULE_NOT_FOUND",l}var c=i[s]={exports:{}};e[s][0].call(c.exports,function(t){return r(e[s][1][t]||t)},c,c.exports,t,e,i,n)}return i[s].exports}for(var o=void 0,s=0;s<n.length;s++)r(n[s]);return r})({1:[function(t,e,i){var n=t("closest"),r=t("component-event"),o=["focus","blur"];i.bind=function(t,e,i,s,a){return -1!==o.indexOf(i)&&(a=!0),r.bind(t,i,function(i){var r=i.target||i.srcElement;i.delegateTarget=n(r,e,!0,t),i.delegateTarget&&s.call(t,i)},a)},i.unbind=function(t,e,i,n){-1!==o.indexOf(e)&&(n=!0),r.unbind(t,e,i,n)}},{closest:2,"component-event":4}],2:[function(t,e,i){var n=t("matches-selector");e.exports=function(t,e,i){for(var r=i?t:t.parentNode;r&&r!==document;){if(n(r,e))return r;r=r.parentNode}}},{"matches-selector":3}],3:[function(t,e,i){var n=Element.prototype,r=n.matchesSelector||n.webkitMatchesSelector||n.mozMatchesSelector||n.msMatchesSelector||n.oMatchesSelector;e.exports=function(t,e){if(r)return r.call(t,e);for(var i=t.parentNode.querySelectorAll(e),n=0;n<i.length;++n)if(i[n]==t)return!0;return!1}},{}],4:[function(t,e,i){var n=window.addEventListener?"addEventListener":"attachEvent",r=window.removeEventListener?"removeEventListener":"detachEvent",o="addEventListener"!==n?"on":"";i.bind=function(t,e,i,r){return t[n](o+e,i,r||!1),i},i.unbind=function(t,e,i,n){return t[r](o+e,i,n||!1),i}},{}],5:[function(t,e,i){function n(){}n.prototype={on:function(t,e,i){var n=this.e||(this.e={});return(n[t]||(n[t]=[])).push({fn:e,ctx:i}),this},once:function(t,e,i){var n=this,r=function(){n.off(t,r),e.apply(i,arguments)};return this.on(t,r,i)},emit:function(t){for(var e=[].slice.call(arguments,1),i=((this.e||(this.e={}))[t]||[]).slice(),n=0,r=i.length;n<r;n++)i[n].fn.apply(i[n].ctx,e);return this},off:function(t,e){var i=this.e||(this.e={}),n=i[t],r=[];if(n&&e)for(var o=0,s=n.length;o<s;o++)n[o].fn!==e&&r.push(n[o]);return r.length?i[t]=r:delete i[t],this}},e.exports=n},{}],6:[function(t,e,i){"use strict";i.__esModule=!0;var n=function(){function t(t,e){for(var i=0;i<e.length;i++){var n=e[i];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,n.key,n)}}return function(e,i,n){return i&&t(e.prototype,i),n&&t(e,n),e}}(),r=function(){function t(e){!function(t,e){if(!(t instanceof e))throw TypeError("Cannot call a class as a function")}(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function(){var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=t.action,this.emitter=t.emitter,this.target=t.target,this.text=t.text,this.trigger=t.trigger,this.selectedText=""},t.prototype.initSelection=function(){if(this.text&&this.target)throw Error('Multiple attributes declared, use either "target" or "text"');if(this.text)this.selectFake();else if(this.target)this.selectTarget();else throw Error('Missing required attributes, use either "target" or "text"')},t.prototype.selectFake=function(){var t=this;this.removeFake(),this.fakeHandler=document.body.addEventListener("click",function(){return t.removeFake()}),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.position="absolute",this.fakeElem.style.left="-9999px",this.fakeElem.style.top=document.body.scrollTop+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,this.selectedText=this.text,document.body.appendChild(this.fakeElem),this.fakeElem.select(),this.copyText()},t.prototype.removeFake=function(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function(){if("INPUT"===this.target.nodeName||"TEXTAREA"===this.target.nodeName)this.target.select(),this.selectedText=this.target.value;else{var t=document.createRange(),e=window.getSelection();t.selectNodeContents(this.target),e.addRange(t),this.selectedText=e.toString()}this.copyText()},t.prototype.copyText=function(){var t=void 0;try{t=document.execCommand(this.action)}catch(e){t=!1}this.handleResult(t)},t.prototype.handleResult=function(t){t?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function(){this.removeFake()},n(t,[{key:"action",set:function(){var t=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=t,"copy"!==this._action&&"cut"!==this._action)throw Error('Invalid "action" value, use either "copy" or "cut"')},get:function(){return this._action}},{key:"target",set:function(t){if(void 0!==t){if(t&&"object"==typeof t&&1===t.nodeType)this._target=t;else throw Error('Invalid "target" value, use a valid Element')}},get:function(){return this._target}}]),t}();i.default=r,e.exports=i.default},{}],7:[function(t,e,i){"use strict";function n(t){return t&&t.__esModule?t:{default:t}}i.__esModule=!0;var r=n(t("./clipboard-action")),o=n(t("delegate-events")),s=function(t){function e(i,n){!function(t,e){if(!(t instanceof e))throw TypeError("Cannot call a class as a function")}(this,e),t.call(this),this.resolveOptions(n),this.delegateClick(i)}return!function(t,e){if("function"!=typeof e&&null!==e)throw TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}(e,t),e.prototype.resolveOptions=function(){var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof t.action?t.action:this.defaultAction,this.target="function"==typeof t.target?t.target:this.defaultTarget,this.text="function"==typeof t.text?t.text:this.defaultText},e.prototype.delegateClick=function(t){var e=this;this.binding=o.default.bind(document.body,t,"click",function(t){return e.onClick(t)})},e.prototype.undelegateClick=function(){o.default.unbind(document.body,"click",this.binding)},e.prototype.onClick=function(t){this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new r.default({action:this.action(t.delegateTarget),target:this.target(t.delegateTarget),text:this.text(t.delegateTarget),trigger:t.delegateTarget,emitter:this})},e.prototype.defaultAction=function(t){return a("action",t)},e.prototype.defaultTarget=function(t){var e=a("target",t);if(e)return document.querySelector(e)},e.prototype.defaultText=function(t){return a("text",t)},e.prototype.destroy=function(){this.undelegateClick(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(n(t("tiny-emitter")).default);function a(t,e){var i="data-clipboard-"+t;if(e.hasAttribute(i))return e.getAttribute(i)}i.default=s,e.exports=i.default},{"./clipboard-action":6,"delegate-events":1,"tiny-emitter":5}]},{},[7])(7)})}},e={};function i(n){var r=e[n];if(void 0!==r)return r.exports;var o=e[n]={exports:{}};return t[n](o,o.exports,i),o.exports}(()=>{i.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return i.d(e,{a:e}),e}})(),(()=>{i.d=(t,e)=>{for(var n in e)i.o(e,n)&&!i.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:e[n]})}})(),(()=>{i.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e)})(),(()=>{"use strict";var t=i(857),e=i.n(t);class n{static ucFirst(t){return t.charAt(0).toUpperCase()+t.slice(1)}static lcFirst(t){return t.charAt(0).toLowerCase()+t.slice(1)}static toDashCase(t){return t.replace(/([A-Z])/g,"-$1").replace(/^-/,"").toLowerCase()}static toLowerCamelCase(t,e){let i=n.toUpperCamelCase(t,e);return n.lcFirst(i)}static toUpperCamelCase(t,e){return e?t.split(e).map(t=>n.ucFirst(t.toLowerCase())).join(""):n.ucFirst(t.toLowerCase())}static parsePrimitive(t){try{return/^\d+(.|,)\d+$/.test(t)&&(t=t.replace(",",".")),JSON.parse(t)}catch(e){return t.toString()}}}class r{static isNode(t){return"object"==typeof t&&null!==t&&(t===document||t===window||t instanceof Node)}static hasAttribute(t,e){if(!r.isNode(t))throw Error("The element must be a valid HTML Node!");return"function"==typeof t.hasAttribute&&t.hasAttribute(e)}static getAttribute(t,e){let i=!(arguments.length>2)||void 0===arguments[2]||arguments[2];if(i&&!1===r.hasAttribute(t,e))throw Error('The required property "'.concat(e,'" does not exist!'));if("function"!=typeof t.getAttribute){if(i)throw Error("This node doesn't support the getAttribute function!");return}return t.getAttribute(e)}static getDataAttribute(t,e){let i=!(arguments.length>2)||void 0===arguments[2]||arguments[2],o=e.replace(/^data(|-)/,""),s=n.toLowerCamelCase(o,"-");if(!r.isNode(t)){if(i)throw Error("The passed node is not a valid HTML Node!");return}if(void 0===t.dataset){if(i)throw Error("This node doesn't support the dataset attribute!");return}let a=t.dataset[s];if(void 0===a){if(i)throw Error('The required data attribute "'.concat(e,'" does not exist on ').concat(t,"!"));return a}return n.parsePrimitive(a)}static querySelector(t,e){let i=!(arguments.length>2)||void 0===arguments[2]||arguments[2];if(i&&!r.isNode(t))throw Error("The parent node is not a valid HTML Node!");let n=t.querySelector(e)||!1;if(i&&!1===n)throw Error('The required element "'.concat(e,'" does not exist in parent node!'));return n}static querySelectorAll(t,e){let i=!(arguments.length>2)||void 0===arguments[2]||arguments[2];if(i&&!r.isNode(t))throw Error("The parent node is not a valid HTML Node!");let n=t.querySelectorAll(e);if(0===n.length&&(n=!1),i&&!1===n)throw Error('At least one item of "'.concat(e,'" must exist in parent node!'));return n}}class o{publish(t){let e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},i=arguments.length>2&&void 0!==arguments[2]&&arguments[2],n=new CustomEvent(t,{detail:e,cancelable:i});return this.el.dispatchEvent(n),n}subscribe(t,e){let i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{},n=this,r=t.split("."),o=i.scope?e.bind(i.scope):e;if(i.once&&!0===i.once){let e=o;o=function(i){n.unsubscribe(t),e(i)}}return this.el.addEventListener(r[0],o),this.listeners.push({splitEventName:r,opts:i,cb:o}),!0}unsubscribe(t){let e=t.split(".");return this.listeners=this.listeners.reduce((t,i)=>([...i.splitEventName].sort().toString()===e.sort().toString()?this.el.removeEventListener(i.splitEventName[0],i.cb):t.push(i),t),[]),!0}reset(){return this.listeners.forEach(t=>{this.el.removeEventListener(t.splitEventName[0],t.cb)}),this.listeners=[],!0}get el(){return this._el}set el(t){this._el=t}get listeners(){return this._listeners}set listeners(t){this._listeners=t}constructor(t=document){this._el=t,t.$emitter=this,this._listeners=[]}}class s{init(){throw Error('The "init" method for the plugin "'.concat(this._pluginName,'" is not defined.'))}update(){}_init(){this._initialized||(this.init(),this._initialized=!0)}_update(){this._initialized&&this.update()}_mergeOptions(t){let i=n.toDashCase(this._pluginName),o=r.getDataAttribute(this.el,"data-".concat(i,"-config"),!1),s=r.getAttribute(this.el,"data-".concat(i,"-options"),!1),a=[this.constructor.options,this.options,t];o&&a.push(window.PluginConfigManager.get(this._pluginName,o));try{s&&a.push(JSON.parse(s))}catch(t){throw console.error(this.el),Error('The data attribute "data-'.concat(i,'-options" could not be parsed to json: ').concat(t.message))}return e().all(a.filter(t=>t instanceof Object&&!(t instanceof Array)).map(t=>t||{}))}_registerInstance(){window.PluginManager.getPluginInstancesFromElement(this.el).set(this._pluginName,this),window.PluginManager.getPlugin(this._pluginName,!1).get("instances").push(this)}_getPluginName(t){return t||(t=this.constructor.name),t}constructor(t,e={},i=!1){if(!r.isNode(t))throw Error("There is no valid element given.");this.el=t,this.$emitter=new o(this.el),this._pluginName=this._getPluginName(i),this.options=this._mergeOptions(e),this._initialized=!1,this._registerInstance(),this._init()}}class a extends s{init(){var t,e,i;this.$container=r.querySelector(document,this.options.pageSelector),this.isMobile=(t=this.$container.getAttribute("data-mobile"))!==null&&void 0!==t&&t,this.isAndroid=(e=this.$container.getAttribute("data-is-android-device"))!==null&&void 0!==e&&e,this.isIos=(i=this.$container.getAttribute("data-is-ios-device"))!==null&&void 0!==i&&i,this.isIos&&this.handleIos(),this.isAndroid&&this.handleAndroid()}handleIos(){this.$_apps=r.querySelector(document,this.options.appSelector),this.$qrCode=r.querySelectorAll(document,this.options.qrCodeSelector),this.$appLinks=r.querySelector(document,this.options.appLinkSelector),this.$banks=r.querySelectorAll(this.$_apps,".bank-logo",!1),this.$banks&&this.$banks.forEach(t=>{t.addEventListener("touchend",e=>{this.onClickBank(e,t)})}),this.$appLinks&&this.$appLinks.addEventListener("change",this.onChangeAppList.bind(this))}handleAndroid(){this.$qrCode=r.querySelectorAll(document,this.options.qrCodeSelector);let t=this.$container.getAttribute("data-android-link");window.location.replace(t);let e=setInterval(()=>{this.showMobileQrCode(),clearInterval(e)},1e3*this.options.appCountDownInterval)}onClickBank(t,e){var i=e.getAttribute("data-link");this.openAppBank(i)}onChangeAppList(t){let e=t.target,i=e.options[e.selectedIndex].value;this.openAppBank(i)}openAppBank(t){if(t)try{window.location.replace(t);let e=setInterval(()=>{window.location.href!==t&&this.showMobileQrCode(),clearInterval(e)},1e3*this.options.appCountDownInterval)}catch(t){this.showMobileQrCode()}}showMobileQrCode(){this.$qrCode.forEach(t=>{t.classList.remove("d-none")})}}a.options={pageSelector:".twint-qr-container",appSelector:"#logo-container",appLinkSelector:"#app-chooser",qrCodeSelector:".qr-code",appCountDownInterval:2};class l{get(t,e){let i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"application/json",n=this._createPreparedRequest("GET",t,i);return this._sendRequest(n,null,e)}post(t,e,i){let n=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"application/json";n=this._getContentType(e,n);let r=this._createPreparedRequest("POST",t,n);return this._sendRequest(r,e,i)}delete(t,e,i){let n=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"application/json";n=this._getContentType(e,n);let r=this._createPreparedRequest("DELETE",t,n);return this._sendRequest(r,e,i)}patch(t,e,i){let n=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"application/json";n=this._getContentType(e,n);let r=this._createPreparedRequest("PATCH",t,n);return this._sendRequest(r,e,i)}abort(){if(this._request)return this._request.abort()}_registerOnLoaded(t,e){e&&t.addEventListener("loadend",()=>{e(t.responseText,t)})}_sendRequest(t,e,i){return this._registerOnLoaded(t,i),t.send(e),t}_getContentType(t,e){return t instanceof FormData&&(e=!1),e}_createPreparedRequest(t,e,i){return this._request=new XMLHttpRequest,this._request.open(t,e),this._request.setRequestHeader("X-Requested-With","XMLHttpRequest"),i&&this._request.setRequestHeader("Content-type",i),this._request}constructor(){this._request=null}}let c="js-pseudo-modal";class h{open(t){this._hideExistingModal(),this._create(),this._open(t)}close(){let t=this.getModal();this._modalInstance=bootstrap.Modal.getInstance(t),this._modalInstance.hide()}getModal(){return this._modal||this._create(),this._modal}updatePosition(){this._modalInstance.handleUpdate()}updateContent(t,e){this._content=t,this._setModalContent(t),this.updatePosition(),"function"==typeof e&&e.bind(this)()}_hideExistingModal(){try{let t=r.querySelector(document,".".concat(c," .modal"),!1);if(!t)return;let e=bootstrap.Modal.getInstance(t);if(!e)return;e.hide()}catch(t){console.warn("[PseudoModalUtil] Unable to hide existing pseudo modal before opening pseudo modal: ".concat(t.message))}}_open(t){this.getModal(),this._modal.addEventListener("hidden.bs.modal",this._modalWrapper.remove),this._modal.addEventListener("shown.bs.modal",t),this._modalInstance.show()}_create(){this._modalMarkupEl=r.querySelector(document,this._templateSelector),this._createModalWrapper(),this._modalWrapper.innerHTML=this._content,this._modal=this._createModalMarkup(),this._modalInstance=new bootstrap.Modal(this._modal,{backdrop:this._useBackdrop}),document.body.insertAdjacentElement("beforeend",this._modalWrapper)}_createModalWrapper(){this._modalWrapper=r.querySelector(document,".".concat(c),!1),this._modalWrapper||(this._modalWrapper=document.createElement("div"),this._modalWrapper.classList.add(c))}_createModalMarkup(){let t=r.querySelector(this._modalWrapper,".modal",!1);if(t)return t;let e=this._modalWrapper.innerHTML;return this._modalWrapper.innerHTML=this._modalMarkupEl.innerHTML,this._setModalContent(e),r.querySelector(this._modalWrapper,".modal")}_setModalTitle(){let t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"";try{r.querySelector(this._modalWrapper,this._templateTitleSelector).innerHTML=t}catch(t){}}_setModalContent(t){let e=r.querySelector(this._modalWrapper,this._templateContentSelector);e.innerHTML=t;try{let t=r.querySelector(e,this._templateTitleSelector);t&&(this._setModalTitle(t.innerHTML),t.parentNode.removeChild(t))}catch(t){}}constructor(t,e=!0,i=".".concat("js-pseudo-modal-template"),n=".".concat("js-pseudo-modal-template-content-element"),r=".".concat("js-pseudo-modal-template-title-element")){this._content=t,this._useBackdrop=e,this._templateSelector=i,this._templateContentSelector=n,this._templateTitleSelector=r}}class u extends s{init(){this.checking=!1,this.client=new l,this.options.useCart||(this.form=this.el.closest(this.options.formSelector)),u.modal||(u.modal=new h("",!0,".js-twint-modal-template",".js-twint-modal-template-content-element",".js-twint-modal-template-title-element")),this._registerEvents()}getLineItems(){if(this.options.useCart)return[];let t=new FormData(this.form),e={},i=/lineItems\[[^\]]+\]\[([^\]]+)\]/;t.forEach((t,n)=>{if(n.startsWith("lineItems")){let r=n.match(i);if(r&&r[1]){switch(r[1]){case"stackable":case"removable":t="1"===t;break;case"quantity":t=parseInt(t)}e[r[1]]=t}}});let n=[];return n.push(e),n}_registerEvents(){this.el.addEventListener("click",this.onClick.bind(this))}getLoadingPopup(){return this.loadingPopup||(this.loadingPopup=window.PluginManager.getPluginInstanceFromElement(document.querySelector("#twint-loading-popup"),"TwintLoadingPopup")),this.loadingPopup}onClick(t){if(!this.checking)return this.checking=!0,this.client.abort(),this.getLoadingPopup().show(),this.client.post("/twint/express-checkout",JSON.stringify({lineItems:this.getLineItems(),useCart:this.options.useCart}),this.onFinish.bind(this)),t.stopPropagation(),t.preventDefault(),!1}onFinish(t,e){if(this.getLoadingPopup().hide(),200===e.status){let e=JSON.parse(t);this.onModalLoaded(e.content);return}this.onError(t)}onModalLoaded(t){this.checking=!1,u.modal.open(),u.modal.updateContent(t),window.PluginManager.initializePlugin("TwintPaymentStatusRefresh","[data-twint-payment-status-refresh]"),window.PluginManager.initializePlugin("TwintCopyToken","[data-twint-copy-token]"),window.PluginManager.initializePlugin("TwintAppSwitchHandler","[data-app-selector]")}onError(t){console.log("Express checkout error: ",t)}constructor(...t){super(...t),this.loadingPopup=null}}u.options={formSelector:"form",useCart:!1},u.modal=null;class d extends s{init(){this.checking=!1,this.client=new l,this.options.expressCheckout?this.checkExpressCheckoutStatus():(this.$container=r.querySelector(document,this.options.containerSelector),this.orderNumber=this.$container.getAttribute("data-order-number"),this.checkRegularCheckoutStatus())}getDomain(){return this.domain="",window.hasOwnProperty("storefrontUrl")&&(this.domain=window.storefrontUrl),this.domain}reachLimit(){return!!this.checking||this.count>10||(this.count++,this.checking=!0,!1)}checkExpressCheckoutStatus(){this.reachLimit()||this.client.get(this.getDomain()+"/payment/monitoring/"+this.options.pairingHash,t=>{let e=JSON.parse(t);this.checking=!1,e.completed?this.loadThankYouPage():setTimeout(this.checkExpressCheckoutStatus.bind(this),this.options.interval)})}loadThankYouPage(){this.client.get(this.getDomain()+"/payment/express/"+this.options.pairingHash,this.ThankYouPageLoaded.bind(this))}ThankYouPageLoaded(t){u.modal.updateContent(t);let e=r.querySelector(document,".js-pseudo-modal .twint-modal .modal-title");e.innerHTML=e.getAttribute("data-finish")}checkRegularCheckoutStatus(){if(this.reachLimit())return;let t=this.getDomain()+"/payment/order/"+this.orderNumber;this.client.get(t,t=>{this.checking=!1;try{let e=JSON.parse(t);"boolean"==typeof e.reload&&e.reload?location.reload():setTimeout(this.checkRegularCheckoutStatus.bind(this),this.options.interval)}catch(t){}},"application/json",!0)}constructor(...t){super(...t),this.count=0}}d.options={containerSelector:".twint-qr-container",pairingHash:null,interval:1e3,expressCheckout:!1};var p=i(158),g=i.n(p);class f extends s{init(){this.input=r.querySelector(this.el,this.options.target),this.button=r.querySelector(this.el,this.options.selector),this.button.addEventListener("click",this.onClick.bind(this)),this.clipboard=new(g())(this.options.selector),this.clipboard.on("success",this.onCopied.bind(this)),this.clipboard.on("error",this.onError.bind(this))}onClick(t){t.preventDefault(),this.input.disabled=!1}onCopied(t){t.clearSelection(),this.button.innerHTML="Copied!",this.button.classList.add("copied"),this.input.disabled=!0}onError(t){console.error("Action:",t.action),console.error("Trigger:",t.trigger)}constructor(...t){super(...t),this.clipboard=null}}f.options={selector:"#btn-copy-token",target:"#qr-token"};let m=window.PluginManager;m.register("TwintAppSwitchHandler",a,"[data-app-selector]"),m.register("TwintExpressCheckoutButton",u,"[data-twint-express-checkout-button]"),m.register("TwintPaymentStatusRefresh",d,"[data-twint-payment-status-refresh]"),m.register("TwintLoadingPopup",class extends s{init(){this.checking=!1,this.el=r.querySelector(document,"#twint-loading-popup")}show(){this.active=!0,this.el.classList.add("active")}hide(){this.active=!1,this.el.classList.remove("active")}constructor(...t){super(...t),this.active=!1}},"[data-twint-loading-popup]"),m.register("TwintCopyToken",f,"[data-twint-copy-token]")})()})();
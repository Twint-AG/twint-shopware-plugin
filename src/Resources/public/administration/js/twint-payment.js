!function(t){function e(e){for(var n,r,o=e[0],a=e[1],s=0,l=[];s<o.length;s++)r=o[s],Object.prototype.hasOwnProperty.call(i,r)&&i[r]&&l.push(i[r][0]),i[r]=0;for(n in a)Object.prototype.hasOwnProperty.call(a,n)&&(t[n]=a[n]);for(c&&c(e);l.length;)l.shift()()}var n={},r={"twint-payment":0},i={"twint-payment":0};function o(e){if(n[e])return n[e].exports;var r=n[e]={i:e,l:!1,exports:{}};return t[e].call(r.exports,r,r.exports,o),r.l=!0,r.exports}o.e=function(t){var e=[];r[t]?e.push(r[t]):0!==r[t]&&{0:1}[t]&&e.push(r[t]=new Promise((function(e,n){for(var i="static/css/"+({}[t]||t)+".css",a=o.p+i,s=document.getElementsByTagName("link"),l=0;l<s.length;l++){var c=(u=s[l]).getAttribute("data-href")||u.getAttribute("href");if("stylesheet"===u.rel&&(c===i||c===a))return e()}var d=document.getElementsByTagName("style");for(l=0;l<d.length;l++){var u;if((c=(u=d[l]).getAttribute("data-href"))===i||c===a)return e()}var p=document.createElement("link");p.rel="stylesheet",p.type="text/css";p.onerror=p.onload=function(i){if(p.onerror=p.onload=null,"load"===i.type)e();else{var o=i&&("load"===i.type?"missing":i.type),s=i&&i.target&&i.target.href||a,l=new Error("Loading CSS chunk "+t+" failed.\n("+s+")");l.code="CSS_CHUNK_LOAD_FAILED",l.type=o,l.request=s,delete r[t],p.parentNode.removeChild(p),n(l)}},p.href=a,document.head.appendChild(p)})).then((function(){r[t]=0})));var n=i[t];if(0!==n)if(n)e.push(n[2]);else{var a=new Promise((function(e,r){n=i[t]=[e,r]}));e.push(n[2]=a);var s,l=document.createElement("script");l.charset="utf-8",l.timeout=120,o.nc&&l.setAttribute("nonce",o.nc),l.src=function(t){return o.p+"static/js/"+{0:"7b8ba551d1f8d9a2a0df",1:"8dd845bd49b72ffc4471",2:"dda85ac91f1c90af1588"}[t]+".js"}(t);var c=new Error;s=function(e){l.onerror=l.onload=null,clearTimeout(d);var n=i[t];if(0!==n){if(n){var r=e&&("load"===e.type?"missing":e.type),o=e&&e.target&&e.target.src;c.message="Loading chunk "+t+" failed.\n("+r+": "+o+")",c.name="ChunkLoadError",c.type=r,c.request=o,n[1](c)}i[t]=void 0}};var d=setTimeout((function(){s({type:"timeout",target:l})}),12e4);l.onerror=l.onload=s,document.head.appendChild(l)}return Promise.all(e)},o.m=t,o.c=n,o.d=function(t,e,n){o.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)o.d(n,r,function(e){return t[e]}.bind(null,r));return n},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,"a",e),e},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o.p=(window.__sw__.assetPath + '/bundles/twintpayment/'),o.oe=function(t){throw console.error(t),t};var a=this["webpackJsonpPlugintwint-payment"]=this["webpackJsonpPlugintwint-payment"]||[],s=a.push.bind(a);a.push=e,a=a.slice();for(var l=0;l<a.length;l++)e(a[l]);var c=s;o(o.s="I+oZ")}({"2Puq":function(t,e,n){var r=n("ncrE");r.__esModule&&(r=r.default),"string"==typeof r&&(r=[[t.i,r,""]]),r.locals&&(t.exports=r.locals);(0,n("P8hj").default)("0cbb2e34",r,!0,{})},"3g9p":function(t,e,n){Shopware.Component.register("twint-payment-plugin-icon",(function(){return n.e(0).then(n.bind(null,"FdAP"))})),Shopware.Component.register("twint-settings",(function(){return n.e(2).then(n.bind(null,"DobE"))})),Shopware.Component.register("express-settings",(function(){return n.e(1).then(n.bind(null,"WR6I"))})),Shopware.Module.register("twint-payment",{type:"plugin",name:"TwintPayment",title:"twint.title",description:"twint.general.descriptionTextModule",version:"1.0.0",targetVersion:"1.0.0",icon:"regular-cog",routeMiddleware:function(t,e){t(e)},routes:{index:{component:"twint-settings",path:"index",meta:{parentPath:"sw.settings.index.plugins"}},express:{component:"express-settings",path:"express",meta:{parentPath:"sw.settings.index.plugins"}}},settingsItem:[{name:"twint-payment-express",to:"twint.payment.express",label:"twint.express.menuItem",group:"plugins",iconComponent:"twint-payment-plugin-icon",backgroundEnabled:!1},{name:"twint-payment",to:"twint.payment.index",label:"twint.general.menuItem",group:"plugins",iconComponent:"twint-payment-plugin-icon",backgroundEnabled:!1}]})},"6D7V":function(t){t.exports=JSON.parse('{"twint":{"name":"TWINT payment","title":"TWINT payment","express":{"title":"TWINT Express Checkout","menuItem":"TWINT Express Checkout"},"general":{"menuItem":"TWINT Credentials","descriptionTextModule":"TWINT is a Swiss payment app that allows you to pay in stores and online shops digitally and cashlessly"},"settings":{"button":{"save":"Save","testing":"Verifying credentials ..."},"testCredentials":{"error":{"title":"Credential Error","message":"Invalid credentials. Please check again: Merchant ID, certificate and environment (mode)"}},"merchantId":{"label":"Merchant ID","placeholder":"Enter your Merchant ID","helpText":"The Merchant ID is provided by TWINT","error":{"title":"Merchant ID","invalidFormat":"Invalid Merchant ID. Merchant ID needs to be a UUIDv4","required":"Merchant ID is required"}},"certificate":{"placeholder":"Upload a certificate file (.p12)","error":{"title":"Invalid Certificate","message":"An error occurred reading the certificate. Please try again ","general":"An error occurred reading the certificate. Please try again","required":"Certificate file is required","ERROR_INVALID_INPUT":"Certificate file and password are required","ERROR_INVALID_UNKNOWN":"Certificate cannot be validated. Please try again","ERROR_INVALID_CERTIFICATE_FORMAT":"Invalid certificate format","ERROR_INVALID_PASSPHRASE":"Invalid passphrase","ERROR_INVALID_ISSUER_COUNTRY":"Invalid issuer country","ERROR_INVALID_ISSUER_ORGANIZATION":"Invalid issuer organization","ERROR_INVALID_EXPIRY_DATE":"Invalid expiry date","ERROR_CERTIFICATE_EXPIRED":"Certificate expired","ERROR_CERTIFICATE_NOT_YET_VALID":"Certificate is not yet valid"},"success":{"title":"Certificate validation successful","message":"Your certificate is successfully validated"},"password":{"label":"Certificate Password","helpText":"Please enter the password for the certificate"}}},"order":{"stateCard":{"label":"Payment has been processed by TWINT","showTransactionLogModal":"Show transaction logs"},"transactionLog":{"list":{"title":"Transactions","columns":{"createdAt":"CreatedAt","order":"Order","transaction":"Transaction","request":"Request","response":"Response","soapRequest":"Request","soapResponse":"Response","exception":"Exception","payment":"Payment","action":{"view":"View detail"}},"modal":{"title":"Log details","close":"Close"}}}}},"sw-order":{"detail":{"twint":"TWINT transaction logs"}}}')},FUWB:function(t,e,n){},"I+oZ":function(t,e,n){"use strict";n.r(e);var r=n("fTAK"),i=n("6D7V");function o(t){return(o="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function a(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function s(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,(i=r.key,a=void 0,a=function(t,e){if("object"!==o(t)||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,e||"default");if("object"!==o(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(i,"string"),"symbol"===o(a)?a:String(a)),r)}var i,a}function l(t,e){return(l=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t})(t,e)}function c(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var n,r=u(t);if(e){var i=u(this).constructor;n=Reflect.construct(r,arguments,i)}else n=r.apply(this,arguments);return d(this,n)}}function d(t,e){if(e&&("object"===o(e)||"function"==typeof e))return e;if(void 0!==e)throw new TypeError("Derived constructors may only return object or undefined");return function(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}(t)}function u(t){return(u=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(t){return t.__proto__||Object.getPrototypeOf(t)})(t)}Shopware.Locale.extend("de-DE",r),Shopware.Locale.extend("en-GB",i);var p=function(t){!function(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),Object.defineProperty(t,"prototype",{writable:!1}),e&&l(t,e)}(o,t);var e,n,r,i=c(o);function o(t,e){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"twint";return a(this,o),i.call(this,t,e,n)}return e=o,(n=[{key:"uploadFile",value:function(t,e){var n=this.getBasicHeaders();n["Content-Type"]="multipart/form-data";var r=new FormData;return r.append("file",t),r.append("password",e),this.httpClient.post("_actions/twint/extract-pem",r,{headers:n})}}])&&s(e.prototype,n),r&&s(e,r),Object.defineProperty(e,"prototype",{writable:!1}),o}(Shopware.Classes.ApiService);function f(t){return(f="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}function m(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function w(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,(i=r.key,o=void 0,o=function(t,e){if("object"!==f(t)||null===t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,e||"default");if("object"!==f(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(i,"string"),"symbol"===f(o)?o:String(o)),r)}var i,o}function g(t,e){return(g=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t})(t,e)}function h(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(t){return!1}}();return function(){var n,r=y(t);if(e){var i=y(this).constructor;n=Reflect.construct(r,arguments,i)}else n=r.apply(this,arguments);return b(this,n)}}function b(t,e){if(e&&("object"===f(e)||"function"==typeof e))return e;if(void 0!==e)throw new TypeError("Derived constructors may only return object or undefined");return function(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}(t)}function y(t){return(y=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(t){return t.__proto__||Object.getPrototypeOf(t)})(t)}var v=Shopware.Classes.ApiService,_=function(t){!function(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),Object.defineProperty(t,"prototype",{writable:!1}),e&&g(t,e)}(o,t);var e,n,r,i=h(o);function o(t,e){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"twint";return m(this,o),i.call(this,t,e,n)}return e=o,(n=[{key:"validateCredential",value:function(t){var e=this.getBasicHeaders();return this.httpClient.post("_actions/twint/validate-api-credential",t,{headers:e}).then((function(t){return v.handleResponse(t)}))}}])&&w(e.prototype,n),r&&w(e,r),Object.defineProperty(e,"prototype",{writable:!1}),o}(v),S=Shopware.Application,I=S.getContainer("init").httpClient,R=Shopware.Service("loginService");S.addServiceProvider("twintFileUploadService",(function(){return new p(I,R)})),S.addServiceProvider("TwintPaymentSettingsService",(function(t){return new _(I,R)}));n("2Puq");var T=Shopware,C=T.Component,x=T.Mixin;C.register("twint-certificate",{template:Shopware.Feature.isActive("v6.6.0.0")?'{% block twint_certificate_block %}\n    <div>\n        <sw-file-input\n                v-model="currentCertFile"\n                :allowed-mime-types="[\'application/x-pkcs12\']"\n                :maxFileSize="8*1024*1024"\n                @update:value="onFileChange"\n                required\n        >\n            <template #caption-label>\n                {{ $tc(\'twint.settings.certificate.placeholder\') }}\n            </template>\n        </sw-file-input>\n\n        <sw-password-field\n                v-model:value="currentPassword"\n                :entity-collection="null"\n                class="twint-config-field"\n                :label="$tc(\'twint.settings.certificate.password.label\')"\n                :help-text="$tc(\'twint.settings.certificate.password.helpText\')"\n                @blur="updatePassword"\n                required\n        >\n            <template #suffix>\n                {% block twint_certificate_password_suffix_block %}\n                    <sw-icon name="regular-low-vision" size="22px"></sw-icon>\n                {% endblock %}\n            </template>\n        </sw-password-field>\n    </div>\n{% endblock %}\n':'{% block twint_certificate_block %}\n    <div>\n        <sw-file-input\n                v-model="currentCertFile"\n                :allowed-mime-types="[\'application/x-pkcs12\']"\n                :maxFileSize="8*1024*1024"\n                @change="onFileChange"\n                required\n        >\n            <template #caption-label>\n                {{ $tc(\'twint.settings.certificate.placeholder\') }}\n            </template>\n        </sw-file-input>\n\n        <sw-password-field\n                v-model:value="currentPassword"\n                :entity-collection="null"\n                class="twint-config-field"\n                :label="$tc(\'twint.settings.certificate.password.label\')"\n                :help-text="$tc(\'twint.settings.certificate.password.helpText\')"\n                @blur="updatePassword">\n            <template #suffix>\n                {% block twint_certificate_password_suffix_block %}\n                    <sw-icon name="regular-low-vision" size="22px"></sw-icon>\n                {% endblock %}\n            </template>\n        </sw-password-field>\n    </div>\n{% endblock %}\n',mixins:[x.getByName("notification")],inject:["feature"],data:function(){return{currentPassword:null,currentCertFile:null}},methods:{onFileChange:function(t){this.currentCertFile=t,this.extractPem()},updatePassword:function(t){this.extractPem()},extractPem:function(){var t,e=this,n=Shopware.Service("twintFileUploadService");this.currentCertFile&&this.currentPassword&&0!==this.currentPassword.length&&n.uploadFile(this.currentCertFile,null!==(t=this.currentPassword)&&void 0!==t?t:"").then((function(t){e.updateCertificate(t.data.data),e.createNotification({title:e.$tc("twint.settings.certificate.success.title"),message:e.$tc("twint.settings.certificate.success.message"),growl:!0}).then((function(t){}))})).catch((function(t){if(400===t.response.status){var n=t.response.data.errorCode;return e.createNotificationError({title:e.$tc("twint.settings.certificate.error.title"),message:e.$tc("twint.settings.certificate.error."+n),growl:!0})}e.createNotificationError({title:e.$tc("twint.settings.certificate.error.title"),message:e.$tc("twint.settings.certificate.error.general"),growl:!0})}))},updateCertificate:function(t){this.feature.isActive("v6.6.0.0")?this.$emit("update:value",t):this.$emit("input",t)}}});n("pfrm"),n("3g9p");Shopware.Component.override("sw-order-detail",{template:'{% block sw_order_detail_content_tabs_extension %}\n    {% parent %}\n    <sw-tabs-item\n            v-if="order?.customFields?.twint_api_response"\n            class="sw-order-detail__tab-twint"\n            :route="{ name: \'sw.order.detail.twint\', params: { id: $route.params.id } }"\n            :title="$tc(\'sw-order.detail.twint\')">\n        {{ $tc(\'sw-order.detail.twint\') }}\n    </sw-tabs-item>\n{% endblock %}',data:function(){return{customFieldSets:[],showStateHistoryModal:!1}}});n("fKsf");var k=Shopware,E=k.Application,P=k.Mixin,A=Shopware.Data.Criteria,O=Shopware.Component.getComponentHelper();O.mapState,O.mapGetters;Shopware.Component.register("sw-order-detail-twint",{template:'<sw-card title="Logs" class="twint-transaction-log" position-identifier="sw-order-detail-twint">\n    {% block sw_order_detail_twint_transaction_log_card_grid %}\n    <template\n            v-if="(transactionLogs && transactionLogs.total > 0)"\n            #grid\n    >\n        {% block twint_transaction_log_list_content %}\n            <sw-entity-listing\n                    class="twint-transaction-logs-grid"\n                    v-if="transactionLogs"\n                    :items="transactionLogs"\n                    :repository="transactionLogRepository"\n                    :columns="transactionLogColumns"\n                    :sort-by="sortBy"\n                    :sort-direction="sortDirection"\n                    :showSelection="false"\n                    :allowDelete="false"\n                    :showDelete="false"\n                    :isLoading="isLoading">\n\n                {% block twint_transaction_log_list_content_order %}\n                    <template #column-orderId="{ item }">\n                        <router-link :to="{ name: \'sw.order.detail\', params: { id: item.orderId }, query: { edit: false } }">\n                            {{ item.order.orderNumber }}\n                        </router-link>\n                    </template>\n                {% endblock %}\n\n                {% block twint_transaction_log_list_content_transaction %}\n                    <template #column-transactionId="{ item }">\n                        {{ item.transactionId }}\n                    </template>\n                {% endblock %}\n\n                {% block twint_transaction_log_list_content_payment %}\n                    <template #column-paymentStateId="{ item }">\n                        <sw-label :variant="getVariantState(\'order_transaction\', item.paymentStateMachineState)" appearance="badged">\n                            {{ item.paymentStateMachineState.name }}\n                        </sw-label>\n                    </template>\n                {% endblock %}\n\n                {% block twint_transaction_log_list_content_order %}\n                    <template #column-orderStateId="{ item }">\n                        <sw-label :variant="getVariantState(\'order\', item.orderStateMachineState)" appearance="badged">\n                            {{ item.orderStateMachineState.name }}\n                        </sw-label>\n                    </template>\n                {% endblock %}\n\n                {% block twint_transaction_log_list_content_created_at %}\n                    <template #column-createdAt="{ item }">\n                        {{ dateFilter(item.createdAt) }}\n                    </template>\n                {% endblock %}\n\n                {% block twint_transaction_log_list_content_actions %}\n                    <template #actions="{ item }">\n                        {% block twint_transaction_log_list_content_actions_view %}\n                            <sw-context-menu-item class="twint-transaction-log-grid-btn-view"\n                                                  @click="onOpenModalDetail(item.id)">\n                                {{ $tc(\'twint.order.transactionLog.list.columns.action.view\') }}\n                            </sw-context-menu-item>\n                        {% endblock %}\n                    </template>\n                {% endblock %}\n                {% block twint_transaction_log_list_content_action_modals %}\n                    <template #action-modals="{ item }">\n                        {% block twint_transaction_log_list_content_action_detail_modal %}\n                            <sw-modal\n                                    v-if="showTransactionLogDetailModal === item.id"\n                                    :title="$tc(\'twint.order.transactionLog.list.modal.title\')"\n                                    variant="large"\n                                    @modal-close="onCloseModalDetail"\n                            >\n                                {% block twint_transaction_log_list_content_action_detail_modal_order %}\n                                    <p class="sw-order-detail-twint-modal-order">\n                                    <b>{{ $tc(\'twint.order.transactionLog.list.columns.order\') }}:</b> {{ item.order.orderNumber }}\n                                    </p>\n                                    <p class="sw-order-detail-twint-modal-transaction">\n                                    <b>{{ $tc(\'twint.order.transactionLog.list.columns.transaction\') }}:</b> {{ item.transactionId }}\n                                    </p>\n                                    <p class="sw-order-detail-twint-modal-payment">\n                                    <b>{{ $tc(\'twint.order.transactionLog.list.columns.payment\') }}:</b> {{ item.paymentStateMachineState.name }}\n                                    </p>\n                                    <p class="sw-order-detail-twint-modal-order">\n                                        <b>{{ $tc(\'twint.order.transactionLog.list.columns.order\') }}:</b> {{ item.orderStateMachineState.name }}\n                                    </p>\n                                    <b class="sw-order-detail-twint-modal-soap-request">\n                                        {{ $tc(\'twint.order.transactionLog.list.columns.request\') }}\n                                    </b>\n                                    <sw-textarea-field :disabled="true" :value="item.request" />\n                                    <b class="sw-order-detail-twint-modal-soap-response">\n                                         {{ $tc(\'twint.order.transactionLog.list.columns.response\') }}\n                                    </b>\n                                    <sw-textarea-field :disabled="true" :value="item.response"/>\n\n                                    <sw-card v-for="(request, index) in item.soapRequest">\n                                            <sw-container>\n                                                    <h4>SOAP {{ index + 1 }}</h4>\n                                                    <b class="sw-order-detail-twint-modal-soap-request">\n                                                        {{ $tc(\'twint.order.transactionLog.list.columns.soapRequest\') }}\n                                                    </b>\n                                                    <sw-textarea-field :disabled="true" :value="request"/>\n\n                                                    <b class="sw-order-detail-twint-modal-soap-response" v-if="item.soapResponse[index]">\n                                                         {{ $tc(\'twint.order.transactionLog.list.columns.soapResponse\') }}\n                                                    </b>\n                                                    <sw-textarea-field  v-if="item.soapResponse[index]" :disabled="true" :value="item.soapResponse[index]"/>\n                                            </sw-container>\n                                    </sw-card>\n                                    <p class="sw-order-detail-twint-modal-exception">\n                                        {{ $tc(\'twint.order.transactionLog.list.columns.exception\') }}\n                                    </p>\n                                    <sw-textarea-field :disabled="true" :value="item.exception"/>\n                                {% endblock %}\n\n                                {% block twint_transaction_log_list_content_action_detail_modal_footer %}\n                                    <template #modal-footer>\n                                        {% block twint_transaction_log_list_content_action_detail_modal_close %}\n                                            <sw-button size="small" @click="onCloseModalDetail">\n                                                {{ $tc(\'twint.order.transactionLog.list.modal.close\') }}\n                                            </sw-button>\n                                        {% endblock %}\n                                    </template>\n                                {% endblock %}\n                            </sw-modal>\n                        {% endblock %}\n                    </template>\n                {% endblock %}\n            </sw-entity-listing>\n        {% endblock %}\n    </template>\n    {% endblock %}\n</sw-card>',mixins:[P.getByName("notification"),P.getByName("listing")],inject:["repositoryFactory","acl","stateStyleDataProviderService"],metaInfo:function(){return{title:this.$createTitle()}},data:function(){return{isLoading:!1,transactionLogs:null,sortBy:"createdAt",sortDirection:"DESC",naturalSorting:!0,showTransactionLogDetailModal:!1}},created:function(){this.createdComponent()},methods:{createdComponent:function(){this.isLoading=!0,this.getList()},getList:function(){var t=this;this.naturalSorting="createdAt"===this.sortBy;var e=new A;e.addSorting(A.sort(this.sortBy,this.sortDirection,this.naturalSorting)),e.addAssociation("order"),e.addAssociation("paymentStateMachineState"),e.addAssociation("orderStateMachineState"),e.addFilter(A.equals("order.id",this.orderId)),this.isLoading=!0,this.transactionLogRepository.search(e).then((function(e){t.transactionLogs=e,t.isLoading=!1})).catch((function(){t.isLoading=!1}))},onOpenModalDetail:function(t){this.showTransactionLogDetailModal=t},onCloseModalDetail:function(){this.showTransactionLogDetailModal=!1},getVariantState:function(t,e){return this.stateStyleDataProviderService.getStyle("".concat(t,".state"),e.technicalName).variant}},computed:{orderId:function(){return this.$route.params.id},transactionLogRepository:function(){return this.repositoryFactory.create("twint_transaction_log")},totalTransactionLogs:function(){return this.transactionLogs.length},transactionLogColumns:function(){var t=E.getApplicationRoot();return t?[{property:"orderId",label:t.$tc("twint.order.transactionLog.list.columns.order"),allowResize:!0},{property:"transactionId",label:t.$tc("twint.order.transactionLog.list.columns.transaction"),allowResize:!0},{property:"paymentStateId",label:t.$tc("twint.order.transactionLog.list.columns.payment"),allowResize:!0,sortable:!1},{property:"orderStateId",label:t.$tc("twint.order.transactionLog.list.columns.order"),allowResize:!0,align:"center"},{property:"createdAt",label:t.$tc("twint.order.transactionLog.list.columns.createdAt"),allowResize:!0}]:[]},dateFilter:function(){return Shopware.Filter.getByName("date")}}}),Shopware.Module.register("twint-sw-order-detail",{type:"plugin",name:"twint",title:"twint.name",description:"twint.pluginDescription",version:"1.0.0",targetVersion:"1.0.0",color:"#333",icon:"default-action-settings",routeMiddleware:function(t,e){"sw.order.detail"===e.name&&e.children.push({name:"sw.order.detail.twint",path:"/sw/order/detail/:id/twint",component:"sw-order-detail-twint",meta:{parentPath:"sw.order.index"}}),t(e)}})},P8hj:function(t,e,n){"use strict";function r(t,e){for(var n=[],r={},i=0;i<e.length;i++){var o=e[i],a=o[0],s={id:t+":"+i,css:o[1],media:o[2],sourceMap:o[3]};r[a]?r[a].parts.push(s):n.push(r[a]={id:a,parts:[s]})}return n}n.r(e),n.d(e,"default",(function(){return m}));var i="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!i)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var o={},a=i&&(document.head||document.getElementsByTagName("head")[0]),s=null,l=0,c=!1,d=function(){},u=null,p="data-vue-ssr-id",f="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function m(t,e,n,i){c=n,u=i||{};var a=r(t,e);return w(a),function(e){for(var n=[],i=0;i<a.length;i++){var s=a[i];(l=o[s.id]).refs--,n.push(l)}e?w(a=r(t,e)):a=[];for(i=0;i<n.length;i++){var l;if(0===(l=n[i]).refs){for(var c=0;c<l.parts.length;c++)l.parts[c]();delete o[l.id]}}}}function w(t){for(var e=0;e<t.length;e++){var n=t[e],r=o[n.id];if(r){r.refs++;for(var i=0;i<r.parts.length;i++)r.parts[i](n.parts[i]);for(;i<n.parts.length;i++)r.parts.push(h(n.parts[i]));r.parts.length>n.parts.length&&(r.parts.length=n.parts.length)}else{var a=[];for(i=0;i<n.parts.length;i++)a.push(h(n.parts[i]));o[n.id]={id:n.id,refs:1,parts:a}}}}function g(){var t=document.createElement("style");return t.type="text/css",a.appendChild(t),t}function h(t){var e,n,r=document.querySelector("style["+p+'~="'+t.id+'"]');if(r){if(c)return d;r.parentNode.removeChild(r)}if(f){var i=l++;r=s||(s=g()),e=v.bind(null,r,i,!1),n=v.bind(null,r,i,!0)}else r=g(),e=_.bind(null,r),n=function(){r.parentNode.removeChild(r)};return e(t),function(r){if(r){if(r.css===t.css&&r.media===t.media&&r.sourceMap===t.sourceMap)return;e(t=r)}else n()}}var b,y=(b=[],function(t,e){return b[t]=e,b.filter(Boolean).join("\n")});function v(t,e,n,r){var i=n?"":r.css;if(t.styleSheet)t.styleSheet.cssText=y(e,i);else{var o=document.createTextNode(i),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(o,a[e]):t.appendChild(o)}}function _(t,e){var n=e.css,r=e.media,i=e.sourceMap;if(r&&t.setAttribute("media",r),u.ssrId&&t.setAttribute(p,e.id),i&&(n+="\n/*# sourceURL="+i.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(i))))+" */"),t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}},fKsf:function(t,e,n){var r=n("FUWB");r.__esModule&&(r=r.default),"string"==typeof r&&(r=[[t.i,r,""]]),r.locals&&(t.exports=r.locals);(0,n("P8hj").default)("1c8c6fb8",r,!0,{})},fTAK:function(t){t.exports=JSON.parse('{"twint":{"title":"TWINT-Anmeldeinformationen","express":{"title":"TWINT Express Checkout","menuItem":"TWINT Express Checkout"},"general":{"menuItem":"TWINT-Anmeldeinformationen","descriptionTextModule":"TWINT ist eine Schweizer Bezahl-App, mit der Sie in Geschäften, online und Geld an Freunde und Familie überweisen können."},"settings":{"button":{"save":"Speichern","testing":"Anmeldeinformationen werden überprüft ..."},"testCredentials":{"error":{"title":"Anmeldeinformationen ungültig","message":"Die Anmeldeinformationen sind ungültig. Bitte überprüfen Sie die Händler-ID, das Zertifikat und die Umgebung (Modus)."}},"merchantId":{"label":"Händler-ID","placeholder":"Geben Sie Ihre Händler-ID ein","helpText":"Die von TWINT bereitgestellte Händler-ID","error":{"title":"Händler-ID","invalidFormat":"Die Händler-ID hat ein ungültiges Format. Muss eine UUIDv4 sein","required":"Händler-ID ist erforderlich"}},"certificate":{"placeholder":"Wählen Sie eine Zertifikatsdatei (.p12)","error":{"title":"Zertifikatsfehler","message":"Beim Lesen des Zertifikats ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.","general":"Beim Lesen des Zertifikats ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.","required":"Zertifikatsdatei ist erforderlich","ERROR_INVALID_INPUT":"Zertifikatsdatei und Passwort sind erforderlich","ERROR_INVALID_UNKNOWN":"Bei der Zertifikatsvalidierung ist etwas schiefgelaufen. Bitte versuchen Sie es erneut.","ERROR_INVALID_CERTIFICATE_FORMAT":"Ungültiges Zertifikatsformat","ERROR_INVALID_PASSPHRASE":"Ungültiges Passwort","ERROR_INVALID_ISSUER_COUNTRY":"Ungültiges Herkunftsland des Ausstellers","ERROR_INVALID_ISSUER_ORGANIZATION":"Ungültige Herkunftsorganisation des Ausstellers","ERROR_INVALID_EXPIRY_DATE":"Ungültiges Ablaufdatum","ERROR_CERTIFICATE_EXPIRED":"Zertifikat abgelaufen","ERROR_CERTIFICATE_NOT_YET_VALID":"Zertifikat noch nicht gültig"},"success":{"title":"Zertifikat erfolgreich","message":"Zertifikat wurde erfolgreich gelesen"},"password":{"label":"Zertifikatspasswort","helpText":"Das Passwort für die Zertifikatsdatei "}}},"order":{"stateCard":{"label":"Zahlung wurde von TWINT verarbeitet","showTransactionLogModal":"Transaktionsprotokolle anzeigen"},"transactionLog":{"list":{"title":"Transaktionen","columns":{"createdAt":"CreatedAt","order":"Bestellen","transaction":"Transaktion","request":"Anfrage","response":"Antwort","soapRequest":"Anfrage","soapResponse":"Antwort","exception":"Ausnahme","payment":"Zahlung","action":{"view":"Details anzeigen"}},"modal":{"title":"Protokolldetails","close":"Schließen"}}}}},"sw-order":{"detail":{"twint":"TWINT-Transaktionsprotokolle"}}}')},ncrE:function(t,e,n){},pfrm:function(t,e){var n=Shopware,r=n.Component,i=n.Mixin;r.extend("twint-merchant-id","sw-text-field",{mixins:[i.getByName("notification")],methods:{onChange:function(t){this.$super("onChange",t),this.isValidUUIDv4(t.target.value)||this.createNotificationError({title:this.$tc("twint.settings.merchantId.error.title"),message:this.$tc("twint.settings.merchantId.error.invalidFormat")})},isValidUUIDv4:function(t){return/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(t)}}})}});
//# sourceMappingURL=twint-payment.js.map
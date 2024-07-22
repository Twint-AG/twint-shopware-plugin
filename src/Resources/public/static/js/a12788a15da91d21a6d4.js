(this["webpackJsonpPlugintwint-payment"]=this["webpackJsonpPlugintwint-payment"]||[]).push([[2],{DobE:function(t,n,e){"use strict";e.r(n);var i=Shopware.Mixin;n.default={template:'{% block twint_payment %}\n    <sw-page class="twint-payment">\n        {% block twint_payment_header %}\n            <template #smart-bar-header>\n                <h2>\n                    {{ $tc(\'sw-settings.index.title\') }}\n                    <sw-icon name="regular-chevron-right-xs" small></sw-icon>\n                    {{ $tc(\'twint.general.menuItem\') }}\n                </h2>\n            </template>\n        {% endblock %}\n\n        {% block twint_payment_actions %}\n            <template #smart-bar-actions>\n                {% block twint_payment_settings_actions_save %}\n                    <sw-button-process\n                            class="sw-settings-login-registration__save-action"\n                            :isLoading="isLoading"\n                            :processSuccess="isSaveSuccessful"\n                            :disabled="isLoading || isTesting || isDisabled"\n                            variant="primary"\n                            @process-finish="saveFinish"\n                            @update-lock="updateLock"\n                            @click="onTest"\n                            >\n                        <span v-if="isTesting">\n                            {{ $tc(\'twint.settings.button.testing\') }}\n                        </span>\n                        <span v-if="!isTesting">\n                            {{ $tc(\'twint.settings.button.save\') }}\n                        </span>\n                    </sw-button-process>\n                {% endblock %}\n            </template>\n        {% endblock %}\n\n        {% block twint_payment_settings_content %}\n            <template #content>\n                <sw-card-view>\n                    <sw-system-config\n                            class="twint-config__wrapper"\n                            ref="systemConfig"\n                            sales-channel-switchable\n                            inherit\n                            domain="TwintPayment.settings"\n                            @config-changed="onChanged"\n                    >\n                    </sw-system-config>\n                </sw-card-view>\n            </template>\n        {% endblock %}\n    </sw-page>\n{% endblock %}\n',inject:["TwintPaymentSettingsService"],mixins:[i.getByName("notification"),i.getByName("sw-inline-snippet")],data:function(){return{isLoading:!1,isTesting:!1,isSaveSuccessful:!1,isTestSuccessful:!1,isDisabled:!1}},metaInfo:function(){return{title:this.$createTitle()}},created:function(){this.$root.$on("update-lock",this.updateLock)},methods:{onChanged:function(t){this.isTestSuccessful=!1,this.isSaveSuccessful=!1},saveFinish:function(){this.isSaveSuccessful=!1},getConfigValue:function(t){var n=this.$refs.systemConfig.actualConfigData,e=n.null,i=this.$refs.systemConfig.currentSalesChannelId;if(null===i)return n.null["TwintPayment.settings.".concat(t)];var s=n[i]["TwintPayment.settings.".concat(t)];return null==s&&(s=e["TwintPayment.settings.".concat(t)]),s},updateLock:function(t){this.isDisabled=t},onSave:function(){var t=this;this.checkRequiredFields()&&(this.isSaveSuccessful=!1,this.isLoading=!0,this.$refs.systemConfig.saveAll().then((function(n){t.isSaveSuccessful=!0})).finally((function(){t.isLoading=!1})))},isValidUUIDv4:function(t){return/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(t)},checkRequiredFields:function(){var t=!0,n=this.getConfigValue("merchantId"),e=this.getConfigValue("certificate");return n&&""!==n.trim()||(this.createNotificationError({title:this.$tc("twint.settings.merchantId.error.title"),message:this.$tc("twint.settings.merchantId.error.required")}),t=!1),t&&!this.isValidUUIDv4(n)&&(this.createNotificationError({title:this.$tc("twint.settings.merchantId.error.title"),message:this.$tc("twint.settings.merchantId.error.invalidFormat")}),t=!1),e||(this.createNotificationError({title:this.$tc("twint.settings.merchantId.error.title"),message:this.$tc("twint.settings.certificate.error.required")}),t=!1),t},onTest:function(){var t=this;if(this.checkRequiredFields()){this.isTesting=!0,this.isTestSuccessful=!1;var n={};this.$refs.systemConfig.config.forEach((function(e){n={cert:t.getConfigValue("certificate"),merchantId:t.getConfigValue("merchantId"),testMode:t.getConfigValue("testMode")}})),this.TwintPaymentSettingsService.validateCredential(n).then((function(n){var e;null!==(e=n.success)&&void 0!==e&&e?(t.isTestSuccessful=!0,t.onSave()):t.createNotificationError({title:t.$tc("twint.settings.testCredentials.error.title"),message:t.$tc("twint.settings.testCredentials.error.message")})})).finally((function(n){t.isTesting=!1}))}}},destroyed:function(){this.$root.$off("update-lock")}}}}]);
//# sourceMappingURL=a12788a15da91d21a6d4.js.map
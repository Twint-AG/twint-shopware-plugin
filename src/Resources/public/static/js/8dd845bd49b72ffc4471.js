(this["webpackJsonpPlugintwint-payment"]=this["webpackJsonpPlugintwint-payment"]||[]).push([[1],{WR6I:function(n,t,s){"use strict";s.r(t);var e=Shopware.Mixin;t.default={template:'{% block twint_payment %}\n    <sw-page class="twint-payment">\n        {% block twint_payment_header %}\n            <template #smart-bar-header>\n                <h2>\n                    {{ $tc(\'sw-settings.index.title\') }}\n                    <sw-icon name="regular-chevron-right-xs" small></sw-icon>\n                    {{ $tc(\'twint.express.title\') }}\n                </h2>\n            </template>\n        {% endblock %}\n\n        {% block twint_payment_actions %}\n            <template #smart-bar-actions>\n                {% block twint_payment_settings_actions_save %}\n                    <sw-button-process\n                            class="sw-settings-login-registration__save-action"\n                            :isLoading="isLoading"\n                            :processSuccess="isSaveSuccessful"\n                            :disabled="isLoading"\n                            variant="primary"\n                            @process-finish="saveFinish"\n                            @click="onSave">\n                        {{ $tc(\'twint.settings.button.save\') }}\n                    </sw-button-process>\n                {% endblock %}\n            </template>\n        {% endblock %}\n\n        {% block twint_payment_settings_content %}\n            <template #content>\n                <sw-card-view>\n                    <sw-system-config\n                            class="twint-config__wrapper"\n                            ref="systemConfig"\n                            sales-channel-switchable\n                            inherit\n                            domain="TwintPayment.express"\n                            @config-changed="onChanged"\n                    >\n                    </sw-system-config>\n                </sw-card-view>\n            </template>\n        {% endblock %}\n    </sw-page>\n{% endblock %}\n',mixins:[e.getByName("sw-inline-snippet")],data:function(){return{isLoading:!1,isSaveSuccessful:!1}},metaInfo:function(){return{title:this.$createTitle()}},methods:{onChanged:function(n){this.isSaveSuccessful=!1},saveFinish:function(){this.isSaveSuccessful=!1},onSave:function(){var n=this;this.isSaveSuccessful=!1,this.isLoading=!0,this.$refs.systemConfig.saveAll().then((function(t){n.isSaveSuccessful=!0})).finally((function(){n.isLoading=!1}))}}}}}]);
//# sourceMappingURL=8dd845bd49b72ffc4471.js.map
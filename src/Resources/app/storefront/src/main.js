// Import all necessary Storefront plugins
import AppSwitchHandler from './twint-payment-plugin/app-switch-handler';
import ExpressCheckoutButton from './twint-payment-plugin/express-checkout-button';
import PaymentStatusRefresh from './twint-payment-plugin/payment-status-refresh';
import LoadingPopup from './twint-payment-plugin/loading-popup';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('AppSwitchHandler', AppSwitchHandler, '[data-app-selector]');
PluginManager.register('TwintExpressCheckoutButton', ExpressCheckoutButton, '[data-twint-express-checkout-button]');
PluginManager.register('TwintPaymentStatusRefresh', PaymentStatusRefresh, '[data-twint-payment-status-refresh]');
PluginManager.register('TwintLoadingPopup', LoadingPopup, '[data-twint-loading-popup]');

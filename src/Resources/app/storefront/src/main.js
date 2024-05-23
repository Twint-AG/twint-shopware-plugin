// Import all necessary Storefront plugins
import AppSwitchHandler from './twint-payment-plugin/app-switch-handler';
import ExpressCheckoutButton from './twint-payment-plugin/express-checkout-button';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('AppSwitchHandler', AppSwitchHandler, '[data-app-selector]');
PluginManager.register('TwintExpressCheckoutButton', ExpressCheckoutButton, '[data-twint-express-checkout-button]');

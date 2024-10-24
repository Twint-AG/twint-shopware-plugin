// Import all necessary Storefront plugins
import AppSwitchHandler from './twint-payment-plugin/app-switch-handler';
import ExpressCheckoutButton from './twint-payment-plugin/express-checkout-button';
import PaymentStatusRefresh from './twint-payment-plugin/payment-status-refresh';
import LoadingPopup from './twint-payment-plugin/loading-popup';
import CopyToken from './twint-payment-plugin/copy-token';
import Feature from 'src/helper/feature.helper';
import CartWidgetPlugin from './header/cart-widget';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;

if (Feature.isActive('v6.6.0.0')) {
  PluginManager.override('CartWidget', () => import('./header/cart-widget'), '[data-cart-widget]');
}else {
  PluginManager.override('CartWidget', CartWidgetPlugin, '[data-cart-widget]');
}
PluginManager.register('TwintAppSwitchHandler', AppSwitchHandler, '[data-app-selector]');
PluginManager.register('TwintExpressCheckoutButton', ExpressCheckoutButton, '[data-twint-express-checkout-button]');
PluginManager.register('TwintPaymentStatusRefresh', PaymentStatusRefresh, '[data-twint-payment-status-refresh]');
PluginManager.register('TwintLoadingPopup', LoadingPopup, '[data-twint-loading-popup]');
PluginManager.register('TwintCopyToken', CopyToken, '[data-twint-copy-token]');

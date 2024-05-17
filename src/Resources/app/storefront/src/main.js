// Import all necessary Storefront plugins
import AppSwitchHandler from './twint-payment-plugin/app-switch-handler';
// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('AppSwitchHandler', AppSwitchHandler, '[data-app-selector]');

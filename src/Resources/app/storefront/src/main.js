// Import all necessary Storefront plugins
import AppSelectorModalHandler from './app-selector-modal-handler/app-selector-modal-handler';
// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('AppSelectorModalHandler', AppSelectorModalHandler, '[data-app-selector]');

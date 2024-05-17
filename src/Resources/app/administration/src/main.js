// Import admin module
import './snippet';
import './service';
import './module/twint-certificate';
import './module/twint-merchant-id';
import './module/payment-setting';
import './module/sw-order/component/sw-order-details-state-card';
Shopware.Component.register('twint-transaction-logs-modal', () => import('./module/sw-order/component/transaction-log-modal'));
import './module/sw-order/page/sw-order-detail-details';


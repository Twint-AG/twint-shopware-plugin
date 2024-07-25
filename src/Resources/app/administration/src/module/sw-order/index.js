import './component/sw-order-create-options'
import './page/sw-order-detail'
import './view/sw-order-detail-details'
import './view/sw-order-detail-twint'


// eslint-disable-next-line no-undef
const {Module} = Shopware;

Module.register('twint-sw-order-detail', {
    type: 'plugin',
    name: 'twint',
    title: 'twint.name',
    description: 'twint.pluginDescription',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#333',
    icon: 'default-action-settings',

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                name: 'sw.order.detail.twint',
                path: '/sw/order/detail/:id/twint',
                component: 'sw-order-detail-twint',
                meta: {
                    parentPath: 'sw.order.index',
                },
            });
        }
        next(currentRoute);
    },
});

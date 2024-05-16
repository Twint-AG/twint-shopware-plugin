Shopware.Component.register('twint-payment-plugin-icon', () => import('./component/twint-payment-plugin-icon'));
Shopware.Component.register('twint-settings', () => import('./page/twint-settings'));
Shopware.Component.register('express-settings', () => import('./page/express-settings'));


Shopware.Module.register('twint-payment', {
    type: 'plugin',
    name: 'TwintPayment',
    title: 'twint.title',
    description: 'twint.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    icon: 'regular-cog',

    routeMiddleware(next, currentRoute) {
        next(currentRoute);
    },

    routes: {
        index: {
            component: 'twint-settings',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        },
        "express": {
            component: 'express-settings',
            path: 'express',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        }
    },

    settingsItem: [
        {
            name: 'twint-payment-express',
            to: 'twint.payment.express',
            label: 'twint.express.menuItem',
            group: 'plugins',
            iconComponent: 'twint-payment-plugin-icon',
            backgroundEnabled: false
        },
        {
            name: 'twint-payment',
            to: 'twint.payment.index',
            label: 'twint.general.menuItem',
            group: 'plugins',
            iconComponent: 'twint-payment-plugin-icon',
            backgroundEnabled: false
        }
    ],
});

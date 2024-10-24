const {Component, Mixin} = Shopware;

Component.extend('twint-store-uuid', 'sw-text-field', {
    mixins: [
        Mixin.getByName('notification'),
    ],

    methods: {
        onChange(event) {
            this.$super('onChange', event);

            if (!this.isValidUUIDv4(event.target.value)) {
                this.createNotificationError({
                    title: this.$tc('twint.settings.storeUuid.error.title'),
                    message: this.$tc('twint.settings.storeUuid.error.invalidFormat')
                });
            }
        },

        isValidUUIDv4(uuid) {
            // Regular expression to match UUID v4 format
            var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

            // Check if the string matches the UUID v4 format
            return uuidRegex.test(uuid);
        }
    }
});

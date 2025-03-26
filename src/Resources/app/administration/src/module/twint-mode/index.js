import template from './twint-mode.html.twig';
import './twint-mode.scss';

const {Component} = Shopware;

let parentComponent = 'sw-switch-field';
if(Component.getComponentRegistry().has('sw-switch-field-deprecated')){
  parentComponent = 'sw-switch-field-deprecated';
}

Component.extend('twint-mode', parentComponent, {
  template: template,
  inject: ['feature', 'systemConfigApiService'],
  props: {
    props: {

      noMarginTop: {
        type: Boolean,
        required: false,
        default: false,
      },

      size: {
        type: String,
        required: false,
        default: 'default',
        validValues: ['small', 'medium', 'default'],
        validator(val) {
          return ['small', 'medium', 'default'].includes(val);
        },
      },
    },
  },
  data() {
    return {
      testMode: false,
    };
  },
  computed: {
    swSwitchFieldClasses() {
      const swSwitchFieldClasses = this.$super('swSwitchFieldClasses') ?? [];
      if (swSwitchFieldClasses?.[0]) {
        swSwitchFieldClasses[0]['sw-field--switch-bordered'] = true;
        if(this.$route.query.showTwintEnvOptions == '0'){
          swSwitchFieldClasses[0]['is--twint-hidden'] = true;
        }
        else if(this.$route.query.showTwintEnvOptions != '1' && this.testMode != true){
          swSwitchFieldClasses[0]['is--twint-hidden'] = true;
        }
      }
      return [
        swSwitchFieldClasses,
        `sw-field--${this.size}`,
      ];

    },
  },
  async created() {
    this.loadSettings();
    if(this.$route.query.showTwintEnvOptions == '0'){
      if (this.feature.isActive('VUE3') || parentComponent === 'sw-switch-field-deprecated') {
        this.$emit('update:value', false);

        return;
      }
      this.$emit('change', false);
      this.testMode = false;
    }
  },
  methods: {
    async loadSettings() {
      this.isLoading = true;

      const settings = await this.systemConfigApiService.getValues('TwintPayment.settings');
      if (Object.keys(settings).length > 0) {
        this.testMode = settings['TwintPayment.settings.testMode'];
      }
      this.isLoading = false;
    },
  }
});

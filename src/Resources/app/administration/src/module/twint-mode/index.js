import template from './twint-mode.html.twig';
import './twint-mode.scss';

const {Component} = Shopware;

let parentComponent = 'sw-switch-field';
if(Component.getComponentRegistry().has('sw-switch-field-deprecated')){
  parentComponent = 'sw-switch-field-deprecated';
}

Component.extend('twint-mode', parentComponent, {
  template: template,
  props: {
    props: {
      value: {
        type: Boolean,
        required: false,
      },

      checked: {
        type: Boolean,
        required: false,
      },

      showTwintEnvOptions: {
        type: Boolean,
        required: false,
        default: false
      }
    },
  },
  computed: {
    swSwitchFieldClasses() {
      let swSwitchFieldClasses = this.$super('swSwitchFieldClasses');
      if(this.$route.query.showTwintEnvOptions != '1'){
        this.showTwintEnvOptions = true;
        swSwitchFieldClasses[0]['is--twint-hidden'] = true;
      }
      return [
        swSwitchFieldClasses,
        `sw-field--${this.size}`,
      ];

    },
  }
});

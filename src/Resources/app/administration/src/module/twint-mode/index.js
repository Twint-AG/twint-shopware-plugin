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
  computed: {
    swSwitchFieldClasses() {
      let swSwitchFieldClasses = this.$super('swSwitchFieldClasses');
      if (typeof swSwitchFieldClasses[0] !== 'undefined') {
        swSwitchFieldClasses[0]['sw-field--switch-bordered'] = true;
        if(this.$route.query.showTwintEnvOptions != '1'){
          swSwitchFieldClasses[0]['is--twint-hidden'] = true;
        }
      }
      return [
        swSwitchFieldClasses,
        `sw-field--${this.size}`,
      ];

    },
  }
});

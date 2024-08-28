import template from './twint-textarea-input.html.twig';

const {Component} = Shopware;

let parentComponent = 'sw-textarea-field';
if(Component.getComponentRegistry().has('sw-textarea-field-deprecated')){
  parentComponent = 'sw-textarea-field-deprecated';
}

Component.extend('twint-textarea-input', parentComponent, {
  template: template,
  props: {
    value: {
      type: String,
      required: false,
      default: null,
    },

    placeholder: {
      type: String,
      required: false,
      default: null,
    },

    maxLength: {
      type: Number,
      required: false,
      default: null,
    },
  },
});

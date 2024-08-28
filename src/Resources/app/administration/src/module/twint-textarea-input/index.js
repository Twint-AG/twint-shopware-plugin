import template from './twint-textarea-input.html.twig';

const {Component} = Shopware;

Component.extend('twint-textarea-input', 'sw-textarea-field', {
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

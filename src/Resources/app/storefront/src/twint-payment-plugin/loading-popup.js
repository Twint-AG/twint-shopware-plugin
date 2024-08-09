import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import items from './points';

export default class LoadingPopup extends Plugin {

  active = false;

  init() {
    this.checking = false;
    this.index = 0;

    this.el = DomAccess.querySelector(document, '#twint-loading-popup');
  }

  show() {
    this.active = true;
    this.el.classList.add('active');
    this.animation = setInterval(this.changePoints.bind(this), 20);
  }

  hide() {
    this.active = false;
    this.el.classList.remove('active');
    if (this.animation) {
      clearInterval(this.animation);
    }
  }

  changePoints() {
    const pointElement = document.getElementById('twintAnimation');
    pointElement.setAttribute('d', String(items[this.index]));

    this.index++;
    if (this.index >= items.length) {
      this.index = 0;
    }
  }
}

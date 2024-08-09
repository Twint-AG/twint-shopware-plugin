import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import items from './points';

export default class LoadingPopup extends Plugin {
  static animation = null;
  static index = 0;
  active = false;

  init() {
    this.checking = false;
    LoadingPopup.index = 0;

    this.el = DomAccess.querySelector(document, '#twint-loading-popup');
  }

  show() {
    this.active = true;
    this.el.classList.add('active');

    if(!LoadingPopup.animation){
      LoadingPopup.animation = setInterval(this.changePoints.bind(this), 20);
    }
  }

  hide() {
    this.active = false;
    this.el.classList.remove('active');
    if (LoadingPopup.animation) {
      clearInterval(LoadingPopup.animation);
      LoadingPopup.animation = null;
    }
  }

  changePoints() {
    const pointElement = document.getElementById('twintAnimation');
    pointElement.setAttribute('d', String(items[LoadingPopup.index]));

    LoadingPopup.index++;
    if (LoadingPopup.index >= items.length) {
      LoadingPopup.index = 0;
    }
  }
}

import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class LoadingPopup extends Plugin {

    active = false;

    init(){
        this.checking = false;

        this.el = DomAccess.querySelector(document, '#twint-loading-popup');
    }

    show(){
        this.active = true;
        this.el.classList.add('active');
    }

    hide(){
        this.active = false;
        this.el.classList.remove('active');
    }
}

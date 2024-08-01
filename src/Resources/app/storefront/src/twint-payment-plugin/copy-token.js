import Plugin from 'src/plugin-system/plugin.class';
import Clipboard from '../librabries/clipboard';
import DomAccess from 'src/helper/dom-access.helper';

export default class CopyToken extends Plugin {

    static options = {
        selector: '#btn-copy-token',
        target: '#qr-token',
    };

    clipboard = null;

    init() {
        this.input = DomAccess.querySelector(this.el, this.options.target);

        this.button = DomAccess.querySelector(this.el, this.options.selector);
        this.button.addEventListener('click', this.onClick.bind(this));

        this.clipboard = new Clipboard(this.options.selector);
        this.clipboard.on('success', this.onCopied.bind(this));
        this.clipboard.on('error', this.onError.bind(this));
    }

    onClick(event) {
        event.preventDefault();
        this.input.disabled = false;
    }

    onCopied(e){
        e.clearSelection();
        this.button.innerHTML = 'Copied!';
        this.button.classList.add('copied');
        this.input.disabled = true
    }

    onError(e){}
}

import Plugin from 'src/plugin-system/plugin.class';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';
import DomAccess from 'src/helper/dom-access.helper';

export default class AppSelectorModalHandler extends Plugin {

    static options = {
        modalBackdrop: false,
        modalId: '#appSelector .modal-content',
        modalClassAttribute: 'data-modal-class',
        modalClass: null
    };

    init() {
        var modalSelector = DomAccess.querySelector(document, this.options.modalId);
        this.openModal(modalSelector.innerHTML);
    }
    openModal(content) {
        // create a new modal instance
        this.modal = new PseudoModalUtil(content);

        // open the modal window and make it visible
        this.modal.open();
    }
}

import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';

export default class TwintModal extends PseudoModalUtil {
  _open(cb) {
    super._open(cb);

    let self = this;
    this._modal.addEventListener('hidden.bs.modal', function (event) {
      self.onClosed(event);
    });
  }

  setOnClosed(cb){
    this.onClosed = cb;
  }
}

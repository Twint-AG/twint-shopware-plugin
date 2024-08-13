import CartWidgetPlugin from 'src/plugin/header/cart-widget.plugin';
import Storage from 'src/helper/storage/storage.helper';


export default class OverrideCartWidget extends CartWidgetPlugin {
  insertStoredContent() {
    let regex = /CHF.*\s*/g;

    let innerHtml = this.el.innerHTML;

    let match = innerHtml.match(regex);
    let amount = match ? match[0] : null;

    if(amount){
      let replaced = amount.replace(/\d/g, '0');
      innerHtml = innerHtml.replace(amount, replaced);
    }

    Storage.setItem(this.options.emptyCartWidgetStorageKey, innerHtml);

    const storedContent = Storage.getItem(this.options.cartWidgetStorageKey);
    if (storedContent) {
      this.el.innerHTML = storedContent;
    }

    this.$emitter.publish('insertStoredContent');
  }
}

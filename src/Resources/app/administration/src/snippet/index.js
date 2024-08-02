import de from './de-DE.json';
import en from './en-GB.json';
import fr from './fr-FR.json';
import it from './it-IT.json';

let map = {
  'en-GB': en,
  'de-DE': de,
  'de-CH': de,
  'fr-FR': fr,
  'fr-CH': fr,
  'it-IT': it,
  'it-CH': it,
};

for (let locale in map) {
  if(Shopware.Locale.getLocaleRegistry().has(locale)){
    Shopware.Locale.extend(locale, map[locale]);
  }else {
    Shopware.Locale.register(locale, map[locale]);
  }
}


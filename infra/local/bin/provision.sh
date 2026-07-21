#!/usr/bin/env bash
# Runs INSIDE a dockware container (piped via `bash -s`). Idempotent.
set -euo pipefail

PLUGIN=TwintPayment
PLUGIN_DIR="/var/www/html/custom/plugins/${PLUGIN}"
HOST="https://shopware.twint.local"
cd /var/www/html

echo "== [1/6] composer install (plugin deps) =="
if [ ! -d "${PLUGIN_DIR}/vendor" ] || [ "${PLUGIN_DIR}/composer.json" -nt "${PLUGIN_DIR}/vendor" ]; then
  composer install -d "${PLUGIN_DIR}" --no-interaction --no-progress
else
  echo "   vendor up to date — skip"
fi

echo "== [2/6] plugin refresh + install/activate =="
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache "${PLUGIN}" \
  || php bin/console plugin:activate "${PLUGIN}" \
  || true
# Assert the plugin is actually installed AND active — else fail loudly.
active=$(mysql -h127.0.0.1 -uroot -proot shopware -N -e \
  "SELECT active FROM plugin WHERE name='${PLUGIN}'" 2>/dev/null || echo "")
if [ "$active" != "1" ]; then
  echo "!! ${PLUGIN} is not installed+active after install — aborting provision" >&2
  exit 1
fi

echo "== [3/6] build admin + storefront assets =="
php bin/console bundle:dump
if [ -f bin/build-administration.sh ]; then bash bin/build-administration.sh; fi
if [ -f bin/build-storefront.sh ]; then bash bin/build-storefront.sh; fi
php bin/console assets:install

echo "== [4/6] shop config: CHF currency / CH country / payment / domain =="
mysql -h127.0.0.1 -uroot -proot shopware <<'SQL'
-- Ensure CHF currency exists (Shopware usually seeds it; create if absent).
INSERT INTO currency (id, iso_code, factor, symbol, position, item_rounding, total_rounding, created_at)
SELECT UNHEX('B7D2554B0CE847CD82F3AC7738289246'), 'CHF', 1, 'CHF', 1,
       '{"decimals":2,"interval":0.05,"roundForNet":true}',
       '{"decimals":2,"interval":0.05,"roundForNet":true}', NOW()
WHERE NOT EXISTS (SELECT 1 FROM currency WHERE iso_code = 'CHF');

INSERT INTO currency_translation (currency_id, language_id, name, short_name, created_at)
SELECT c.id, l.id, 'Swiss franc', 'CHF', NOW()
FROM currency c CROSS JOIN language l
WHERE c.iso_code = 'CHF'
  AND NOT EXISTS (
    SELECT 1 FROM currency_translation ct WHERE ct.currency_id = c.id AND ct.language_id = l.id
  );

-- Resolve the default Storefront sales channel + CHF + CH.
SET @sc  := (SELECT sc.id FROM sales_channel sc
             WHERE sc.type_id = UNHEX('8A243080F92E4C719546314B577CF82B') LIMIT 1);
SET @chf := (SELECT id FROM currency WHERE iso_code = 'CHF' LIMIT 1);
SET @ch  := (SELECT id FROM country  WHERE iso = 'CH' LIMIT 1);

-- Activate CH; make it + CHF part of the sales channel and its defaults.
UPDATE country SET active = 1 WHERE id = @ch;
UPDATE sales_channel SET currency_id = @chf, country_id = @ch WHERE id = @sc;
INSERT IGNORE INTO sales_channel_currency (sales_channel_id, currency_id) VALUES (@sc, @chf);
INSERT IGNORE INTO sales_channel_country  (sales_channel_id, country_id)  VALUES (@sc, @ch);

-- Point the sales-channel domain at the https host (and CHF).
UPDATE sales_channel_domain
   SET url = 'https://shopware.twint.local', currency_id = @chf
 WHERE sales_channel_id = @sc
 ORDER BY (url LIKE 'http://localhost%') DESC
 LIMIT 1;

-- Activate all TwintPayment payment methods and assign them to the sales channel.
UPDATE payment_method SET active = 1
 WHERE plugin_id = (SELECT id FROM plugin WHERE name = 'TwintPayment');
INSERT IGNORE INTO sales_channel_payment_method (sales_channel_id, payment_method_id)
SELECT @sc, pm.id FROM payment_method pm
 WHERE pm.plugin_id = (SELECT id FROM plugin WHERE name = 'TwintPayment');
SQL

echo "== [5/6] cache clear =="
php bin/console cache:clear

echo "== [6/6] done -> ${HOST} =="

<?php declare(strict_types=1);

namespace Twint\Core\Util;

class PemExtractor
{
    public function extractFromPKCS12(string $pkcs12, string $password)
    {
        $p12privkey = null;
        $worked = openssl_pkcs12_read($pkcs12, $p12privkey, $password);

        if ($worked) {
            return $p12privkey;
        }

        return null;
    }
}

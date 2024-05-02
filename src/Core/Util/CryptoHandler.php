<?php declare(strict_types=1);

namespace Twint\Core\Util;

class CryptoHandler
{
    const CIPHERING = "AES-128-CBC";

    public function __construct(private readonly string $key)
    {
    }

    /**
     * @var false|int
     */
    private $iv_length;

    /**
     * @var false|string
     */
    private $encryption_key;

    /**
     * @param string $data
     * @return string
     */
    public function encrypt(string $data): string
    {
        $ivLen = openssl_cipher_iv_length(self::CIPHERING);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $ciphertext_raw = openssl_encrypt($data, self::CIPHERING, $this->key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $this->key, $as_binary = true);

        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    /**
     * @param string $encodedData
     * @return string
     */
    public function decrypt(string $encodedData): string
    {
        $c = base64_decode($encodedData);
        $ivLen = openssl_cipher_iv_length(self::CIPHERING);
        $iv = substr($c, 0, $ivLen);
        $hmac = substr($c, $ivLen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivLen + $sha2len);

        return (string)openssl_decrypt($ciphertext_raw, self::CIPHERING, $this->key, $options = OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $data
     * @return string
     */
    public function hash(string $data): string
    {
        $hexString = unpack('H*', $data);
        $hex = array_shift($hexString);

        return base64_encode($hex);
    }

    /**
     * @param string $encodedData
     * @return string
     */
    public function unHash(string $encodedData): string
    {
        return hex2bin(base64_decode($encodedData));
    }
}

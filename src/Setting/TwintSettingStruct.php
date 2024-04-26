<?php

namespace Twint\Setting;

use Shopware\Core\Framework\Struct\Struct;

class TwintSettingStruct extends Struct
{
    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var bool
     */
    protected $testMode = false;

    /**
     * @var string
     */
    protected $certificate;

    /**
     * @var string
     */
    protected $screens;

    /**
     * @var int
     */
    protected $onlyPickOrderFromMinutes;

    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        return (string)$this->merchantId;
    }

    /**
     * @param string $merchantId
     *
     * @return self
     */
    public function setMerchantId(string $merchantId): self
    {
        $this->merchantId = $merchantId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTestMode(): bool
    {
        return (bool)$this->testMode;
    }

    /**
     * @param bool $testMode
     *
     * @return self
     */
    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        return (bool)$this->testMode;
    }

    /**
     * @return array
     */
    public function getCertificate(): array
    {
       return (array)$this->certificate;
    }

    /**
     * @param array $certificate
     *
     * @return self
     */
    public function setCertificate(array $certificate): self
    {
        $this->certificate = $certificate;
        return $this;
    }
    /**
     * @return array
     */
    public function getScreens(): array
    {
        return $this->screens;
    }
    /**
     * @param array $screens
     *
     * @return self
     */
    public function setScreens(array $screens): self
    {
        $this->screens = $screens;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOnlyPickOrderFromMinutes(): ?int
    {
        return (int)$this->onlyPickOrderFromMinutes;
    }

    /**
     * @param bool $shopwareFailedPayment
     *
     * @return self
     */
    public function setOnlyPickOrderFromMinutes(int $onlyPickOrderFromMinutes): self
    {
        $this->onlyPickOrderFromMinutes = $onlyPickOrderFromMinutes;
        return $this;
    }
}

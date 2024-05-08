<?php

declare(strict_types=1);

namespace Twint\Core\Model;

use Shopware\Core\Framework\Struct\Struct;

final class TwintSettingStruct extends Struct
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
     * @var array
     */
    protected $certificate;

    /**
     * @var array
     */
    protected $screens;

    public function getMerchantId(): string
    {
        return (string) $this->merchantId;
    }

    public function setMerchantId(string $merchantId): self
    {
        $this->merchantId = $merchantId;
        return $this;
    }

    public function getTestMode(): bool
    {
        return (bool) $this->testMode;
    }

    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;
        return $this;
    }

    public function isTestMode(): bool
    {
        return (bool) $this->testMode;
    }

    public function getCertificate(): array
    {
        return (array) $this->certificate;
    }

    public function setCertificate(array $certificate): self
    {
        $this->certificate = $certificate;
        return $this;
    }

    public function getScreens(): array
    {
        return $this->screens;
    }

    public function setScreens(array $screens): self
    {
        $this->screens = $screens;
        return $this;
    }
}

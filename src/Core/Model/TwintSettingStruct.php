<?php

declare(strict_types=1);

namespace Twint\Core\Model;

use Shopware\Core\Framework\Struct\Struct;

class TwintSettingStruct extends Struct
{
    /**
     * @var string
     */
    protected $storeUuid;

    /**
     * @var bool
     */
    protected $testMode = false;

    /**
     * @var array
     */
    protected $certificate;

    protected ?array $screens;

    protected bool $validated = false;

    public function getStoreUuid(): string
    {
        return (string) $this->storeUuid;
    }

    public function setStoreUuid(string $value): self
    {
        $this->storeUuid = $value;
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
        return $this->screens ?? [];
    }

    public function setScreens(array $screens): self
    {
        $this->screens = $screens;
        return $this;
    }

    public function getValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): self
    {
        $this->validated = $validated;
        return $this;
    }
}

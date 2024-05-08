<?php

declare(strict_types=1);

namespace Twint\FastCheckout\Model;

use Shopware\Core\Framework\Struct\Struct;

//TODO we need define the properties of the class need for the FastCheckoutButton
class FastCheckoutButton extends Struct
{
    private string $name;

    private string $description;

    private string $image;

    private string $link;

    public function __construct(string $name = '', string $description = '', string $image = '', string $link = '')
    {
        $this->name = $name;
        $this->description = $description;
        $this->image = $image;
        $this->link = $link;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}

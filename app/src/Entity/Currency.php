<?php

declare(strict_types=1);


namespace App\Entity;


use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ApiResource(
 *     shortName="Currency",
 *     normalizationContext={
 *          "groups"={"currency:read"}
 *     },
 *     itemOperations={
 *          "get"
 *     },
 *     collectionOperations={
 *          "get"
 *     }
 * )
 */
class Currency
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"currency:read"})
     * @Assert\NotBlank()
     */
    public string $symbol;

    /**
     * @Groups({"currency:read"})
     * @Assert\NotBlank()
     */
    public string $name;

    /**
     * @param string $symbol
     * @param string $name
     */
    public function __construct(string $symbol, string $name)
    {
        $this->symbol = $symbol;
        $this->name = $name;
    }
}

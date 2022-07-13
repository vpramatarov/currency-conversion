<?php


namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     shortName="Rate",
 *     normalizationContext={
 *          "groups"={"rate:read"}
 *     },
 *     itemOperations={
 *          "get"
 *     },
 *     collectionOperations={}
 * )
 */
class Rate
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $pair;

    /**
     * @Groups({"rate:read"})
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min=3,
     *     max=3,
     *     maxMessage="Base currency must be 3 characters"
     * )
     */
    public string $base;

    /**
     * @Groups({"rate:read"})
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min=3,
     *     max=3,
     *     maxMessage="Target currency must be 3 characters"
     * )
     */
    public string $target;

    /**
     * @Groups({"rate:read"})
     * @Assert\NotBlank()
     */
    public float $exchangeRate;

    /**
     * @Assert\NotBlank()
     */
    public string $suffix;


    /**
     * @Groups({"rate:read"})
     * @Assert\NotBlank()
     * @return string
     */
    public function getTrend(): string
    {
        return sprintf('%s %s', $this->exchangeRate, $this->suffix);
    }

}
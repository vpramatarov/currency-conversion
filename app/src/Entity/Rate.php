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
     * @Groups({"rate:read"})
     * @Assert\Length(
     *     min=6,
     *     minMessage="Both currencies must be at least 3 characters long each"
     * )
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
     * @Groups({"rate:read"})
     * @Assert\NotBlank()
     */
    public string $trend;

}
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
 *          "get"={
 *              "openapi_context"={
 *                  "parameters"={
 *                      {
 *                          "name"="_provider",
 *                          "in"="query",
 *                          "description"="Set Provider. Currently supports APILAYER. Default: 'APILAYER'",
 *                          "schema"={
 *                              "type"="string"
 *                          }
 *                      }
 *                  }
 *               }
 *          }
 *     },
 *     collectionOperations={}
 * )
 */
class Rate
{
    /**
     * 2 currency codes separated by underscore. Ex: 'CAD_CHF'.
     *
     * @ApiProperty(identifier=true)
     */
    public string $pair;

    /**
     * @Assert\NotBlank()
     */
    public string $provider;

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
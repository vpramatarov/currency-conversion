<?php


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
 *     collectionOperations={
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
     * @Assert\NotBlank()
     */
    public string $provider;

    /**
     * @Groups({"currency:read"})
     * @Assert\NotBlank()
     */
    public string $name;

    /**
     * @param string $symbol
     * @param string $provider
     * @param string $name
     */
    public function __construct(string $symbol, string $provider, string $name)
    {
        $this->symbol = $symbol;
        $this->provider = $provider;
        $this->name = $name;
    }

}
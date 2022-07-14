<?php


namespace App\Serializer;


use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;


final class DefaultContextBuilder implements SerializerContextBuilderInterface
{
    private SerializerContextBuilderInterface $decorated;

    public function __construct(SerializerContextBuilderInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $className = $request->attributes->get('_api_resource_class');
        $parts = explode('\\', $className);
        $entityName = strtoupper(end($parts));

        $provider = sprintf('%s.%s', $entityName, strtoupper($request->get('_provider', 'APILAYER')));

        // set provider
        $context['_provider'] = $provider;

        return $context;
    }

}
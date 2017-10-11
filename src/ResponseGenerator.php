<?php
declare(strict_types=1);

namespace Lcobucci\Chimera\Routing\Expressive;

use Fig\Http\Message\StatusCodeInterface as ResponseStatus;
use Lcobucci\Chimera\Routing\Attributes;
use Lcobucci\Chimera\Routing\ResponseGenerator as ResponseGeneratorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Router\RouterInterface;

final class ResponseGenerator implements ResponseGeneratorInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ResultConverter
     */
    private $converter;

    public function __construct(RouterInterface $router, ResultConverter $converter)
    {
        $this->router    = $router;
        $this->converter = $converter;
    }

    public function generateResponse(ServerRequestInterface $request, $result = null): ResponseInterface
    {
        $headers = $this->buildHeaders($request);

        if ($result !== null) {
            return $this->converter->convert($request, $headers, $result);
        }

        return new EmptyResponse($this->responseCode($request, $headers), $headers);
    }

    private function buildHeaders(ServerRequestInterface $request): array
    {
        $generatedId = $request->getAttribute(Attributes::GENERATED_ID);

        if ($generatedId === null) {
            return [];
        }

        return [
            'Location' => $this->router->generateUri(
                $request->getAttribute(Attributes::RESOURCE_LOCATION),
                ['id' => (string) $generatedId]
            )
        ];
    }

    private function responseCode(ServerRequestInterface $request, array $headers): int
    {
        $asynchronous = $request->getAttribute(Attributes::ASYNCHRONOUS, false);

        if ($asynchronous) {
            return ResponseStatus::STATUS_ACCEPTED;
        }

        return isset($headers['Location']) ? ResponseStatus::STATUS_CREATED : ResponseStatus::STATUS_NO_CONTENT;
    }
}

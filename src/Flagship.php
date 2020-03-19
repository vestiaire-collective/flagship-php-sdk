<?php

declare(strict_types=1);

namespace Wcomnisky\Flagship;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Wcomnisky\Flagship\Api\RequestParameters;
use Wcomnisky\Flagship\Context\ContextInterface;

/**
 * Class Flagship
 * @package Wcomnisky\Flagship
 * @see http://developers.flagship.io/api/v1/
 */
class Flagship
{
    private const NAMED_PARAM_ENV_ID = '%ENVIRONMENT_ID';
    private const NAMED_PARAM_CAMPAIGN_ID = '%CAMPAIGN_ID';
    private const NAMED_PARAM_BASE_URL = '%BASE_URL';

    private const URL_ALL_CAMPAIGNS = self::NAMED_PARAM_BASE_URL . '/' . self::NAMED_PARAM_ENV_ID . '/campaigns';
    private const URL_SINGLE_CAMPAIGN = self::URL_ALL_CAMPAIGNS . '/' . self::NAMED_PARAM_CAMPAIGN_ID;
    private const URL_CAMPAIGN_ACTIVATION = self::NAMED_PARAM_BASE_URL . '/activate';

    /**
     * @var string the base url of flagship endpoints ex: https://decision-api.flagship.io/v1
     */
    private $baseUrl;

    /**
     * @var RequestParameters
     * @see http://developers.flagship.io/api/v1/#request-parameters
     */
    private $requestParameters;

    /**
     * @var string The environment ID identifies your account and environment
     */
    private $environmentId;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * Flagship constructor.
     *
     * @param string              $baseUrl
     * @param string              $environmentId
     * @param HttpClientInterface $httpClient
     */
    public function __construct(string $baseUrl, string $environmentId, HttpClientInterface $httpClient)
    {
        if (empty($baseUrl)) {
            throw new \InvalidArgumentException('baseUrl cannot be empty');
        }

        if (empty($environmentId)) {
            throw new \InvalidArgumentException('Environment ID cannot be empty');
        }

        $this->baseUrl = $baseUrl;
        $this->environmentId = $environmentId;
        $this->httpClient = $httpClient;
        $this->requestParameters = new RequestParameters();
    }

    /**
     * Sets the Request Parameters
     *
     * @param RequestParameters $requestParameters
     */
    public function setRequestParameters(RequestParameters $requestParameters): void
    {
        $this->requestParameters = $requestParameters;
    }

    /**
     * Affects a visitor to a variation. It should be use to manually activate a single
     * campaign and variation in case you do not automatically activate them when running
     * campaign assignment (See trigger_hit parameter)
     *
     * It returns a 204 HTTP response
     *
     * @param string $visitorId
     * @param string $variationGroupId
     * @param string $variationId
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     * @see http://developers.flagship.io/api/v1/?shell#campaign-activation
     * @see http://developers.flagship.io/api/v1/?shell#trigger-hit
     */
    public function requestCampaignActivation(
        string $visitorId,
        string $variationGroupId,
        string $variationId
    ): ResponseInterface {

        $jsonArray = [
            'vid' => $visitorId,
            'cid' => $this->environmentId,
            'caid' => $variationGroupId,
            'vaid' => $variationId
        ];

        $response = $this->httpClient->request(
            'POST',
            $this->getCompaignActivationUrl(),
            [
                'json' => $jsonArray
            ]
        );

        return $response;
    }

    /**
     * Retrieves the affection of your visitor ID with a specific
     * context (key/value pairs) to the specified campaign ID.
     *
     * By default, the API will send a hit to trigger a campaign
     * assignment event for the visitor ID and the affected campaign.
     *
     * @param string $visitorId ID of the visitor
     * @param string $campaignId The same as Custom ID on their docs
     * @param ContextInterface $context
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function requestSingleCampaign(
        string $visitorId,
        string $campaignId,
        ContextInterface $context
    ): ResponseInterface {

        $jsonArray = [
            'visitor_id' => $visitorId,
            'context' => !empty($context->getList()) ? $context->getList() : new \stdClass(),
            'decision_group' => $this->requestParameters->getDecisionGroup(),
            'format_response' => $this->requestParameters->isFormatResponseEnabled(),
            'trigger_hit' => $this->requestParameters->isTriggerHitEnabled()
        ];

        $response = $this->httpClient->request(
            'POST',
            $this->getSingleCampaignUrl($campaignId),
            [
                'json' => $jsonArray
            ]
        );

        return $response;
    }

    /**
     * Retrieves all the campaigns affected to your visitor and the context (key/value pairs).
     *
     * By default, the API will send a hit to trigger a campaign
     * assignment event for the visitor ID and each affected campaign.
     *
     * @param string $visitorId
     * @param ContextInterface $context
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function requestAllCampaigns(string $visitorId, ContextInterface $context): ResponseInterface
    {
        $jsonArray = [
            'visitor_id' => $visitorId,
            'context' => !empty($context->getList()) ? $context->getList() : new \stdClass(),
            'decision_group' => $this->requestParameters->getDecisionGroup(),
            'trigger_hit' => $this->requestParameters->isTriggerHitEnabled()
        ];

        $response = $this->httpClient->request(
            'POST',
            $this->getAllCampaignsUrl(),
            [
                'json' => $jsonArray
            ]
        );

        return $response;
    }

    /**
     * Replaces the named parameter (key) with its correlated value
     *
     * @param string $source
     * @param array $namedParamKVP
     * @return string
     */
    private function replaceNamedParameter(string $source, array $namedParamKVP): string
    {
        return str_replace(array_keys($namedParamKVP), array_values($namedParamKVP), $source);
    }

    /**
     * Returns the SingleCampaign URL with the Environment ID and Campaign ID in place
     *
     * @param string $campaignId
     * @return string
     */
    private function getSingleCampaignUrl(string $campaignId): string
    {
        return $this->replaceNamedParameter(
            self::URL_SINGLE_CAMPAIGN,
            [
                self::NAMED_PARAM_BASE_URL => $this->baseUrl,
                self::NAMED_PARAM_ENV_ID => $this->environmentId,
                self::NAMED_PARAM_CAMPAIGN_ID => $campaignId
            ]
        );
    }

    /**
     * @return string
     */
    private function getCompaignActivationUrl(): string
    {
        return $this->replaceNamedParameter(
            self::URL_CAMPAIGN_ACTIVATION,
            [
                self::NAMED_PARAM_BASE_URL => $this->baseUrl
            ]
        );
    }

    /**
     * Returns the AllCampaigns URL with the Environment ID in place. If the Mode is
     * different than normal (default) it is appended to the URL as a Query String
     *
     * @return string
     */
    private function getAllCampaignsUrl(): string
    {
        $url = $this->replaceNamedParameter(
            self::URL_ALL_CAMPAIGNS,
            [
                self::NAMED_PARAM_BASE_URL => $this->baseUrl,
                self::NAMED_PARAM_ENV_ID => $this->environmentId
            ]
        );

        if (! $this->requestParameters->isDefaultMode()) {
            $url .= '?mode=' . $this->requestParameters->getMode();
        }

        return $url;
    }
}

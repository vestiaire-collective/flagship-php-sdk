<?php

declare(strict_types=1);

namespace Wcomnisky\Flagship\UnitTests;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Wcomnisky\Flagship\Api\RequestParameters;
use Wcomnisky\Flagship\Context\ContextInterface;
use Wcomnisky\Flagship\Flagship;

class FlagshipTest extends TestCase
{
    private const ENV_ID = 'cj12clb73ggr1p9ie64h';
    private const BASE_URL = 'https://fake-url-cj12clb73ggr1p9ie64h';

    /**
     * @covers Wcomnisky\Flagship\Flagship::__construct
     */
    public function test_successful_new_instance()
    {
        $flagship = new Flagship(self::BASE_URL, self::ENV_ID, $this->createMock(HttpClientInterface::class));
        $this->assertInstanceOf(Flagship::class, $flagship);
    }

    /**
     * @covers Wcomnisky\Flagship\Flagship::__construct
     */
    public function test_new_instance_with_empty_environmentId_should_throw_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Flagship(self::BASE_URL, '', $this->createMock(HttpClientInterface::class));
    }

    /**
     * @covers Wcomnisky\Flagship\Flagship::__construct
     */
    public function test_new_instance_with_empty_base_url_should_throw_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Flagship('', '', $this->createMock(HttpClientInterface::class));
    }

    /**
     * @covers \Wcomnisky\Flagship\Flagship::requestSingleCampaign
     * @covers \Wcomnisky\Flagship\Flagship::__construct
     * @covers \Wcomnisky\Flagship\Flagship::getSingleCampaignUrl
     * @covers \Wcomnisky\Flagship\Flagship::replaceNamedParameter
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::getMode
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::isTriggerHitEnabled
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::getDecisionGroup()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::isFormatResponseEnabled()
     */
    public function test_successful_requestSingleCampaign_with_no_request_parameters()
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $environmentId = 'my_environment_id';
        $visitorId = 'my_visitor_id';
        $campaignId = 'my_campaign_id';

        $httpClient->expects($this->once())
            ->method('request')
             ->with(
                 $this->identicalTo('POST'),
                 $this->identicalTo(sprintf('%s/%s/campaigns/%s', self::BASE_URL, $environmentId, $campaignId)),
                 $this->callback(function ($options) use ($visitorId) {
                    $expectedOptions = [
                        'json' => [
                            'visitor_id' => $visitorId,
                            'context' => [],
                            'decision_group' => null,
                            'format_response' => false,
                            'trigger_hit' => true
                        ]
                    ];

                    return $options === $expectedOptions;
                 })
             )->willReturn($response);

        $flagship = new Flagship(self::BASE_URL, $environmentId, $httpClient);
        $returnedResponse = $flagship->requestSingleCampaign($visitorId, $campaignId, $context);
        $this->assertSame($response, $returnedResponse);
    }

    /**
     * @covers \Wcomnisky\Flagship\Flagship::requestAllCampaigns
     * @covers \Wcomnisky\Flagship\Flagship::__construct
     * @covers \Wcomnisky\Flagship\Flagship::getAllCampaignsUrl
     * @covers \Wcomnisky\Flagship\Flagship::replaceNamedParameter
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::isDefaultMode()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::getMode()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::isTriggerHitEnabled()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::getDecisionGroup()
     */
    public function test_successful_requestAllCampaigns_with_no_request_parameters()
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $environmentId = 'my_environment_id';
        $visitorId = 'my_visitor_id';

        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->identicalTo('POST'),
                $this->identicalTo(sprintf('%s/%s/campaigns', self::BASE_URL, $environmentId)),
                $this->callback(function ($options) use ($visitorId) {
                    $expectedOptions = [
                        'json' => [
                            'visitor_id' => $visitorId,
                            'context' => [],
                            'decision_group' => null,
                            'trigger_hit' => true
                        ]
                    ];

                    return $options === $expectedOptions;
                })
            )->willReturn($response);

        $flagship = new Flagship(self::BASE_URL, $environmentId, $httpClient);
        $returnedResponse = $flagship->requestAllCampaigns($visitorId, $context);
        $this->assertSame($response, $returnedResponse);
    }

    /**
     * @covers \Wcomnisky\Flagship\Flagship::requestAllCampaigns
     * @covers \Wcomnisky\Flagship\Flagship::__construct
     * @covers \Wcomnisky\Flagship\Flagship::getAllCampaignsUrl
     * @covers \Wcomnisky\Flagship\Flagship::replaceNamedParameter
     * @covers \Wcomnisky\Flagship\Flagship::setRequestParameters
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::isDefaultMode()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::getMode()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::isTriggerHitEnabled()
     * @uses \Wcomnisky\Flagship\Api\RequestParameters::getDecisionGroup()
     */
    public function test_successful_requestAllCampaigns_with_mode_simple_should_append_query_string_to_URL()
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $requestParameters = $this->createMock(RequestParameters::class);

        $environmentId = 'my_environment_id';
        $visitorId = 'my_visitor_id';

        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->identicalTo('POST'),
                $this->identicalTo(sprintf('%s/%s/campaigns?mode=simple', self::BASE_URL, $environmentId)),
                $this->callback(function ($options) use ($visitorId) {
                    $expectedOptions = [
                        'json' => [
                            'visitor_id' => $visitorId,
                            'context' => [],
                            'decision_group' => null,
                            'trigger_hit' => true
                        ]
                    ];

                    return $options === $expectedOptions;
                })
            )->willReturn($response);

        $requestParameters->expects($this->once())
            ->method('getMode')
            ->willReturn('simple');

        $requestParameters->expects($this->once())
            ->method('isTriggerHitEnabled')
            ->willReturn(true);

        $flagship = new Flagship(self::BASE_URL, $environmentId, $httpClient);
        $flagship->setRequestParameters($requestParameters);
        $returnedResponse = $flagship->requestAllCampaigns($visitorId, $context);
        $this->assertSame($response, $returnedResponse);
    }

    /**
     * @covers \Wcomnisky\Flagship\Flagship::requestCampaignActivation
     * @covers \Wcomnisky\Flagship\Flagship::__construct
     * @covers \Wcomnisky\Flagship\Flagship::getCompaignActivationUrl
     * @covers \Wcomnisky\Flagship\Flagship::replaceNamedParameter
     */
    public function test_successful_requestCampaignActivation()
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $environmentId = 'my_environment_id';
        $visitorId = 'my_visitor_id';
        $variationGroupId = 'my_variation_group_id';
        $variationId = 'my_variation_id';

        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->identicalTo('POST'),
                $this->identicalTo(sprintf('%s/activate', self::BASE_URL)),
                $this->callback(function ($options) use ($environmentId, $visitorId, $variationGroupId, $variationId) {
                    $expectedOptions = [
                        'json' => [
                            'vid' => $visitorId,
                            'cid' => $environmentId,
                            'caid' => $variationGroupId,
                            'vaid' => $variationId
                        ]
                    ];

                    return $options === $expectedOptions;
                })
            )->willReturn($response);

        $flagship = new Flagship(self::BASE_URL, $environmentId, $httpClient);
        $returnedResponse = $flagship->requestCampaignActivation($visitorId, $variationGroupId, $variationId);
        $this->assertSame($response, $returnedResponse);
    }
}

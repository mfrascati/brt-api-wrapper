<?php

namespace Andyts93\BrtApiWrapper\Request;

use Andyts93\BrtApiWrapper\Api\LabelParameter;
use Andyts93\BrtApiWrapper\Exception\InvalidJsonException;
use Andyts93\BrtApiWrapper\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ReflectionObject;

abstract class BaseRequest implements RequestInterface
{
    protected $account;
    protected $endpoint;
    protected $method = 'POST';
    protected $apiProperties = [];
    protected $dataWrapper = 'data';
    protected $mandatoryFields = [];
    protected $isLabelRequired;

    protected $senderCustomerCode;
    /**
     * @var int
     */
    protected $numericSenderReference;

    /**
     * @var string
     */
    protected $alphanumericSenderReference;

    /**
     * @var LabelParameter
     */
    protected $labelParameters;

    public function __construct($userId, $password)
    {
        $this->account = [
            'userID' => $userId,
            'password' => $password
        ];
    }

    /**
     * Performs http call to BRT API
     *
     * @throws GuzzleException
     * @throws InvalidJsonException|RequestException
     */
    public function call()
    {
        $client = new Client([
            'base_uri' => 'https://api.brt.it/rest/v1/',
            'timeout' => 2.0
        ]);

        $response = $client->request($this->method, $this->endpoint, [
            'json' => $this->createRequestBody()
        ]);

        // $response = $client->send($request);

        $response = json_decode($response->getBody());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg(), json_last_error());
        }

        return $response;
    }

    public function toArray()
    {
        return [
            $this->dataWrapper => array_filter([
                'senderCustomerCode' => $this->senderCustomerCode,
                'numericSenderReference' => $this->numericSenderReference,
                'alphanumericSenderReference' => $this->alphanumericSenderReference
            ], function ($v) {
                return !is_null($v);
            })
        ];
    }

    public function createRequestBody()
    {
        $arr = $this->toArray();
        $emptyMandatory = [];
        foreach ($arr[$this->dataWrapper] as $k => $v) {
            if (in_array($k, $this->mandatoryFields) && (is_null($v) || $v === "")) {
                $emptyMandatory[$k] = $v;
            }
        }
        if (count($emptyMandatory) > 0) {
            throw new RequestException(sprintf('Fields %s are mandatory', join(', ', array_keys($emptyMandatory))));
        }
        return array_merge(['account' => $this->account], $arr);
    }

    /**
     * @param mixed $isLabelRequired
     * @return BaseRequest
     */
    public function setIsLabelRequired($isLabelRequired)
    {
        $this->isLabelRequired = $isLabelRequired;
        return $this;
    }

    /**
     * @param LabelParameter $labelParameters
     * @return BaseRequest
     */
    public function setLabelParameters($labelParameters)
    {
        $this->labelParameters = $labelParameters;
        return $this;
    }

    /**
     * @param mixed $senderCustomerCode
     * @return BaseRequest
     */
    public function setSenderCustomerCode($senderCustomerCode)
    {
        $this->senderCustomerCode = $senderCustomerCode;
        return $this;
    }

    /**
     * @param int $numericSenderReference
     * @return BaseRequest
     */
    public function setNumericSenderReference($numericSenderReference)
    {
        $this->numericSenderReference = $numericSenderReference;
        return $this;
    }

    /**
     * @param string $alphanumericSenderReference
     * @return BaseRequest
     */
    public function setAlphanumericSenderReference($alphanumericSenderReference)
    {
        $this->alphanumericSenderReference = $alphanumericSenderReference;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumericSenderReference()
    {
        return $this->numericSenderReference;
    }
}

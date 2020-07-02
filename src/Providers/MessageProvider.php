<?php

namespace Para\Providers;

use Para\Config;
use Para\Exceptions\ParaNetworkException;
use Para\Helpers\CurlWrapper;

/**
 * Class MessageProvider
 * @package Para\Providers
 */
class MessageProvider
{
    const TOKEN_TTL_EXTRA_RENEW_BUFFER_TIME_SEC = 10;

    private $appId = '';
    private $appSecret = '';
    private $requestWrapper;

    private $token = '';
    private $tokenTTL = 0;
    private $tokenIssued = 0;

    /**
     * MessageProvider constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $credentials          = $config->getRouteeCredentials();
        $this->requestWrapper = new CurlWrapper();
        $this->appId          = $credentials['appId'];
        $this->appSecret      = $credentials['appSecret'];
    }

    private function getAuthCredentialsString()
    {
        return base64_encode(vsprintf('%s:%s', [$this->appId, $this->appSecret]));
    }

    private function isTokenOutdated()
    {
        return time() > $this->tokenIssued + $this->tokenTTL - static::TOKEN_TTL_EXTRA_RENEW_BUFFER_TIME_SEC;
    }

    private function isRequestSuccessful()
    {
        $transferInfo = $this->requestWrapper->getTransferInfo();
        return 200 === $transferInfo['http_code'];
    }

    /**
     * @param string $url
     * @param array  $headers
     * @param string $payload
     * @return bool|string
     * @throws ParaNetworkException
     */
    private function sendPostRequest($url, $headers, $payload)
    {
        $this->requestWrapper->resetAll();
        return $this->requestWrapper->executeRequest($url, CurlWrapper::HTTP_METHOD_POST, [], $headers, $payload);
    }

    private function sendAuthorizeRequest()
    {
        $data = $this->sendPostRequest(
            'https://auth.routee.net/oauth/token',
            [
                "Authorization" => 'Basic ' . $this->getAuthCredentialsString(),
                "Content-Type"  => "application/x-www-form-urlencoded"
            ],
            'grant_type=client_credentials'
        );

        if ($this->isRequestSuccessful() && !empty($parsedResponse = json_decode($data, true))) {
            $this->token       = $parsedResponse['access_token'];
            $this->tokenTTL    = (int)$parsedResponse['expires_in'];
            $this->tokenType   = $parsedResponse['token_type'];
            $this->tokenIssued = time();
        } else {
            throw new ParaNetworkException('Authorization failed.');
        }
    }

    /**
     * @return string
     * @throws ParaNetworkException
     */
    private function getAuthToken()
    {
        if ($this->isTokenOutdated()) {
            $this->sendAuthorizeRequest();
        }

        return $this->token;
    }


    /**
     * @param string $recipient
     * @param string $message
     * @param string $sender
     * @return mixed
     */
    public function sendMessage($recipient, $message, $sender = 'PHP Script')
    {
        $result = $this->sendPostRequest(
            'https://connect.routee.net/sms',
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->getAuthToken()
            ],
            json_encode(
                [
                    'body' => $message,
                    'from' => $sender,
                    'to'   => $recipient,
                ]
            )
        );

        if ($this->isRequestSuccessful() && !empty($parsedResponse = json_decode($result, true))) {
            return $parsedResponse['trackingId'];
        } else {
            throw new ParaNetworkException('Sending failed.');
        }
    }
}

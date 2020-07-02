<?php

namespace Para\Providers;

use Para\Config;
use Para\Exceptions\ParaNetworkException;
use Para\Helpers\CurlWrapper;

class OpenWeatherMapProvider
{

    private $apiKey = '';

    private $requestWrapper;

    private $defaultQueryString = [];

    const KELVIN_OFFSET = 273.15;

    const REQUEST_ENDPOINT = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct(Config $config)
    {
        $this->defaultQueryString['appid'] = $this->apiKey = $config->getOwmCredentials()['apiKey'];
        $this->requestWrapper              = new CurlWrapper();
    }

    private function prepareQueryParams($query)
    {
        return array_merge($this->defaultQueryString, ['q' => $query]);
    }

    /**
     * @param string $city
     * @return string
     * @throws ParaNetworkException
     */
    private function getRawDataByCity($city)
    {
        $queryParams = $this->prepareQueryParams($city);
        $this->requestWrapper->resetAll();
        $this
            ->requestWrapper
            ->getQueryParams()
            ->setBatch($queryParams);
        $response = $this->requestWrapper->executeRequest(static::REQUEST_ENDPOINT);

        $transferInfo = $this->requestWrapper->getTransferInfo();
        if (200 === $transferInfo['http_code']) {
            return $response;
        } else {
            throw new ParaNetworkException('Request failed');
        }
    }

    private function getTemperatureByCityKelvin($city)
    {
        $response       = $this->getRawDataByCity($city);
        $parsedResponse = json_decode($response, true);

        if (is_array($parsedResponse) && !empty($parsedResponse['main']['temp'])) {
            return $parsedResponse['main']['temp'];
        } else {
            throw new ParaNetworkException('Invalid response');
        }
    }

    public function getCelsiusTemperatureByCity($city)
    {
        return $this->getTemperatureByCityKelvin($city) - static::KELVIN_OFFSET;
    }
}

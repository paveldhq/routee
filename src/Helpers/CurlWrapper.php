<?php

namespace Para\Helpers;

use Para\Exceptions\ParaEnvironmentException;
use Para\Exceptions\ParaNetworkException;

/**
 * Class CurlWrapper
 *
 * Since avoid using extra libraries use it. But really prefer not to waste time on it and use Guzzle.
 * Hope someone looks on it.
 *
 * @package Para\Helpers
 */
class CurlWrapper
{
    const HTTP_METHOD_GET  = 'GET';
    const HTTP_METHOD_POST = 'POST';

    private $defaultOptions = [
        CURLOPT_RETURNTRANSFER => 1,
        //CURLOPT_HEADER         => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_ENCODING       => "",
        //CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
    ];

    /**
     * @var resource cURL handle
     */
    protected $ch = null;

    /**
     * @var KeyValueContainerHelper
     */
    protected $headers;

    /**
     * @var KeyValueContainerHelper
     */
    protected $options;

    /**
     * @var KeyValueContainerHelper
     */
    protected $queryParams;

    /**
     * @return KeyValueContainerHelper
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return KeyValueContainerHelper
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return KeyValueContainerHelper
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @var string
     */
    protected $responseString = '';

    /**
     * @var array
     */
    protected $transferInfo = [];

    /**
     * @throws ParaNetworkException
     */
    private function initCurl()
    {
        $this->ch = curl_init();

        if (!$this->ch) {
            throw new ParaNetworkException('curl initialization failed.');
        }

        $this->initDefaultOptions();
    }


    private function freeCurl()
    {
        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }

        $this->ch = null;
    }

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new ParaEnvironmentException('Required cURL extension is not loaded.');
        }

        $this->headers     = new KeyValueContainerHelper();
        $this->options     = new KeyValueContainerHelper();
        $this->queryParams = new KeyValueContainerHelper();

        $this->initCurl();
    }

    private function initDefaultOptions()
    {
        $this->getOptions()->setBatch($this->defaultOptions);
    }

    public function __destruct()
    {
        $this->freeCurl();
    }

    public function getTransferInfo()
    {
        return $this->transferInfo;
    }


    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $queryParams
     * @param array  $headers
     * @param string $payload
     * @return bool|string
     * @throws ParaNetworkException
     */
    public function executeRequest(
        $endpoint,
        $method = self::HTTP_METHOD_GET,
        $queryParams = [],
        $headers = [],
        $payload = ''
    ) {
        $this->getOptions()->set(CURLOPT_URL, $endpoint);

        $this->setRequestMethod($method);

        if (!empty($queryParams)) {
            $this->getQueryParams()->setBatch($queryParams);
        }

        if (!empty($headers)) {
            $this->getHeaders()->setBatch($headers);
        }

        if (self::HTTP_METHOD_POST === $method && !empty($payload)) {
            $this->getOptions()->set(CURLOPT_POSTFIELDS, $payload);
            $this->getHeaders()->set('Content-Length', strlen($payload));
        }

        if (!empty($this->getQueryParams()->getAll())) {
            $this->prepareQueryParams();
        }

        if (!empty($this->getHeaders()->getAll())) {
            $this->getOptions()->set(CURLOPT_HTTPHEADER, $this->prepareHeaders());
        }
        $optSetResult = curl_setopt_array($this->ch, $this->getOptions()->getAll());

        if (false === $optSetResult) {
            throw new ParaNetworkException('Failed setting options');
        }

        $responseString     = curl_exec($this->ch);
        $this->transferInfo = curl_getinfo($this->ch);

        if ($responseString === false) {
            throw new ParaNetworkException(var_export($this->transferInfo, true));
        }

        return $responseString;
    }


    public function resetCurl()
    {
        $this->freeCurl();
        $this->transferInfo = [];
        $this->initCurl();
    }

    public function resetAll()
    {
        $this->getHeaders()->clearAll();
        $this->getOptions()->clearAll();
        $this->getQueryParams()->clearAll();
        $this->resetCurl();
    }

    /**
     * Build url string from parts produced by parse_str()
     *
     * @param array $parsedUrl
     * @return string
     */
    protected function buildUrl(array $parsedUrl)
    {
        $url = '';

        if (!empty($parsedUrl['scheme'])) {
            $url .= $parsedUrl["scheme"] . '://';
        }

        if (!empty($parsedUrl['user'])) {
            $url .= $parsedUrl['user'];
            if (!empty($parsedUrl['pass'])) {
                $url .= ':' . $parsedUrl['pass'] . '@';
            } else {
                $url .= '@';
            }
        }

        if (!empty($parsedUrl['host'])) {
            $url .= $parsedUrl['host'];
        }

        if (!empty($parsedUrl['port'])) {
            $url .= ':' . $parsedUrl['port'];
        }

        if (!empty($parsedUrl['path'])) {
            $url .= $parsedUrl['path'];
        }

        if (!empty($parsedUrl['query'])) {
            $url .= '?' . $parsedUrl['query'];
        }

        if (!empty($parsedUrl['fragment'])) {
            $url .= '#' . $parsedUrl['fragment'];
        }

        return $url;
    }

    protected function prepareQueryParams()
    {
        $parsedUrl = parse_url($this->getOptions()->get(CURLOPT_URL));
        $query     = http_build_query($this->getQueryParams()->getAll());

        if (isset($parsedUrl['query'])) {
            $parsedUrl['query'] .= '&' . $query;
        } else {
            $parsedUrl['query'] = $query;
        }

        $this->getOptions()->set(CURLOPT_URL, $this->buildUrl($parsedUrl));
    }

    protected function prepareHeaders()
    {
        $headers = [];

        foreach ($this->getHeaders()->getAll() as $header => $value) {
            $headers[] = vsprintf('%s:%s', [$header, $value]);
        }

        return $headers;
    }

    /**
     * Sets the HTTP request method
     *
     * @param string $method
     */
    protected function setRequestMethod($method)
    {
        $this->getOptions()->clear(CURLOPT_NOBODY);
        $this->getOptions()->clear(CURLOPT_HTTPGET);
        $this->getOptions()->clear(CURLOPT_POST);
        $this->getOptions()->clear(CURLOPT_CUSTOMREQUEST);

        switch (strtoupper($method)) {
            case static::HTTP_METHOD_GET:
                $this->getOptions()->set(CURLOPT_HTTPGET, true);
                break;
            case static::HTTP_METHOD_POST:
                $this->getOptions()->set(CURLOPT_POST, true);
                break;
            default:
                $this->getOptions()->set(CURLOPT_CUSTOMREQUEST, $method);
        }
    }
}

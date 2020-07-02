<?php


namespace Para;

use Para\Exceptions\ParaConfigException;

/**
 * Class Config
 * @package Para
 */
class Config
{
    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var array
     */
    private $owmCredentials = [];

    /**
     * @var array
     */
    private $routeeCredentials = [];

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function getOwmCredentials()
    {
        return $this->owmCredentials;
    }

    /**
     * @return array
     */
    public function getRouteeCredentials()
    {
        return $this->routeeCredentials;
    }

    /**
     * Config constructor.
     * @param array $config
     * @throws ParaConfigException
     */
    public function __construct(array $config)
    {
        if (isset($config['settings'])) {
            $this->settings = $config['settings'];
            $this->validateSettingsBlock();
        } else {
            throw new ParaConfigException('"Settings" block required in the config file');
        }

        if (isset($config['credentials']['openweathermap'])) {
            $this->owmCredentials = $config['credentials']['openweathermap'];
            if (!isset($this->owmCredentials['apiKey'])) {
                throw new ParaConfigException('Missing required openweathermap credentials');
            }
            $this->validateSettingsBlock();
        } else {
            throw new ParaConfigException('"credentials->openweathermap" block required in the config file');
        }

        if (isset($config['credentials']['routee'])) {
            $this->routeeCredentials = $config['credentials']['routee'];
            if (!isset($this->routeeCredentials['appId']) || !isset($this->routeeCredentials['appSecret'])) {
                throw new ParaConfigException('Missing required routee credentials');
            }
            $this->validateSettingsBlock();
        } else {
            throw new ParaConfigException('"credentials->routee" block required in the config file');
        }

    }

    /**
     * @throws ParaConfigException
     */
    private function validateSettingsBlock()
    {
        $keys = [
            'city',
            'checkIntervalMinutes',
            'thresholdTemperatureC',
            'name',
            'recipient',
            'sender',
            'aboveMessage',
            'belowMessage'
        ];

        foreach ($keys as $key) {
            if (!isset($this->getSettings()[$key])) {
                throw new ParaConfigException('Missing required config key: ' . $key);
            }
        }
    }
}

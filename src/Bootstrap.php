<?php

namespace Para;

use Para\Exceptions\ParaConfigException;
use Para\Jobs\NotificationJob;

class Bootstrap
{
    const DEFAULT_CONFIG_FILE = 'config.json';

    /**
     * @var Config
     */
    private $config;

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    public function __construct($configFile = self::DEFAULT_CONFIG_FILE)
    {
        $expectedConfigFile = CONFIG_DIR . DIRECTORY_SEPARATOR . $configFile;
        if (file_exists($expectedConfigFile)) {
            $config = json_decode(file_get_contents($expectedConfigFile), true);
            if (null === $config) {
                throw new ParaConfigException('Config file cannot be parsed.');
            } else {
                $this->config = new Config($config);
            }
        } else {
            throw new ParaConfigException('Config file not found.');
        }
    }

    public function run()
    {
        $notificationJob = new NotificationJob($this->getConfig());

        while (true) {
            $notificationJob->run();
        }
    }
}

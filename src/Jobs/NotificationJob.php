<?php

namespace Para\Jobs;

use Para\Config;
use Para\Helpers\LoggerHelper;
use Para\Providers\MessageProvider;
use Para\Providers\OpenWeatherMapProvider;

/**
 * Class NotificationJob
 * @package Para\Jobs
 */
class NotificationJob
{
    private $weatherProvider;

    private $notificationProvider;

    private $settings;

    private $lastExecuted = 0;

    /**
     * @return string
     */
    private function getCity()
    {
        return $this->settings['city'];
    }

    /**
     * @return string
     */
    private function getName()
    {
        return $this->settings['name'];
    }

    /**
     * @return int
     */
    private function getCheckInterval()
    {
        return 60 * (int)$this->settings['checkIntervalMinutes'];
    }

    /**
     * @return float
     */
    private function getThresholdTemperatureC()
    {
        return 1.0 * (float)$this->settings['thresholdTemperatureC'];
    }

    /**
     * @return string
     */
    private function getRecipient()
    {
        return $this->settings['recipient'];
    }

    /**
     * @return string
     */
    private function getSender()
    {
        return $this->settings['sender'];
    }

    /**
     * @return string
     */
    private function getAboveMessage()
    {
        return $this->settings['aboveMessage'];
    }

    /**
     * @return string
     */
    private function getBelowMessage()
    {
        return $this->settings['belowMessage'];
    }

    public function __construct(Config $config)
    {
        $this->weatherProvider = new OpenWeatherMapProvider($config);
        $this->notificationProvider = new MessageProvider($config);
        $this->settings = $config->getSettings();
    }

    private function getTemperature()
    {
        return $this->weatherProvider->getCelsiusTemperatureByCity($this->getCity());
    }

    private function sendMessage($message)
    {
        $msgId = $this->notificationProvider->sendMessage($this->getRecipient(), $message, $this->getSender());
        /**
         * Normally here the successful sending should be logged.
         * Using simple LoggerHelper:
         */
        LoggerHelper::log(vsprintf('Message "%s" sent, trackingId: %s.', [$message, $msgId]));
    }


    /**
     * main infinity loop for the job, since task doesn't tell about cron job.
     * simple blocking implementation
     */
    public function run()
    {
        if (time() > $this->lastExecuted + $this->getCheckInterval()) {
            try {
                $temperature = $this->getTemperature();
                $template    = $temperature > $this->getThresholdTemperatureC()
                    ? $this->getAboveMessage()
                    : $this->getBelowMessage();

                $message = str_replace(
                    ['%name%', '%threshold%', '%temp%'],
                    [$this->getName(), $this->getThresholdTemperatureC(), $temperature],
                    $template
                );
                $this->sendMessage($message);
                $this->lastExecuted = time();
            } catch (\Exception $e) {
                /**
                 * Normally use Monolog to log issue
                 */
                LoggerHelper::log(
                    vsprintf('Issue happened in %s:%s. Message: %s', [$e->getFile(), $e->getLine(), $e->getMessage()])
                );
            }
        }
    }
}

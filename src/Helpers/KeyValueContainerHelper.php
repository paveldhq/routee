<?php


namespace Para\Helpers;

/**
 * Class KeyValueContainerHelper
 * @package Para\Helpers
 */
class KeyValueContainerHelper
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * @param string $name
     * @param string $value
     * @return KeyValueContainerHelper
     */
    public function set($name, $value)
    {
        $this->container[$name] = $value;
        return $this;
    }

    /**
     * @param array $nameValueList
     * @return KeyValueContainerHelper
     */
    public function setBatch(array $nameValueList)
    {
        foreach ($nameValueList as $name => $value) {
            $this->set($name, $value);
        }
        return $this;
    }

    /**
     * @param string $name
     */
    public function clear($name)
    {
        if (isset($this->container[$name])) {
            unset($this->container[$name]);
        }
    }

    public function clearAll()
    {
        $this->container = [];
    }

    /**
     * @param string      $name
     * @param string|null $defaultValue
     * @return string|null
     */
    public function get($name, $defaultValue = null)
    {
        if (isset($this->container[$name])) {
            return $this->container[$name];
        }
        return $defaultValue;
    }

    public function getAll()
    {
        return $this->container;
    }
}

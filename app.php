#!/usr/bin/env php
<?php

/**
 * Since due to restrictions only vanilla PHP can be used, so replacing {{composer}} with this implementation
 */

use Para\Bootstrap;

(new class {

    /**
     * list of namespace => path from composer.json
     * @var array
     */
    private $autoloadMaps = [];

    /**
     * Simple file check
     * @param string $filename
     * @return bool
     */
    private function isFileReachable($filename)
    {
        return file_exists($filename)
            && is_file($filename)
            && is_readable($filename);
    }

    /**
     * Tries to use {{composer}} autoloader if possible, returns {{true}} on success, {{false}} otherwise
     * @return bool
     */
    private function tryUseComposer()
    {
        $expectedComposerAutoloader = ROOT_DIR . '/vendor/autoload.php';
        $result                     = false;
        if ($this->isFileReachable($expectedComposerAutoloader)) {
            /** @noinspection PhpIncludeInspection */
            require_once $expectedComposerAutoloader;
            $result = true;
        }
        return $result;
    }

    /**
     * Fallback embedded autoloader
     * @param string $className
     */
    public function loader($className)
    {
        $className = ltrim($className, '\\');
        foreach ($this->autoloadMaps as $ns => $path) {
            if (0 === strpos($className, $ns)) {
                $expectedFileName = ROOT_DIR
                    . DIRECTORY_SEPARATOR
                    . $path
                    . str_replace('\\', DIRECTORY_SEPARATOR, str_replace($ns, '', $className)) . '.php';

                if ($this->isFileReachable($expectedFileName)) {
                    /** @noinspection PhpIncludeInspection */
                    require_once $expectedFileName;
                }
            }
        }
    }

    public function __construct()
    {
        $this->initPaths();
        $this->initLoader();
    }

    private function initPaths()
    {
        define('ROOT_DIR', __DIR__);
        define('CONFIG_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'config');
    }

    private function initEmbeddedAutoloader()
    {
        $composerJsonFile = ROOT_DIR . '/composer.json';
        if ($this->isFileReachable($composerJsonFile)) {
            try {
                $composerConfig = json_decode(file_get_contents($composerJsonFile), true);
                if (is_array($composerConfig) && isset($composerConfig["autoload"]["psr-4"])
                    && is_array($composerConfig["autoload"]["psr-4"])) {
                    foreach ($composerConfig["autoload"]["psr-4"] as $ns => $path) {
                        $this->autoloadMaps[$ns] = $path;
                    }
                } else {
                    throw new \Exception('Autoload section not found');
                }
            } catch (\Exception $e) {
                die ('Autoload failed: ' . $e->getMessage());
            }
        }
    }

    private function initLoader()
    {
        if (!$this->tryUseComposer()) {
            $this->initEmbeddedAutoloader();
            spl_autoload_register([$this, 'loader']);
        }
    }

    public function boot()
    {
        (new Bootstrap())->run();
    }
})->boot();

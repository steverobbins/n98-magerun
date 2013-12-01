<?php

namespace N98\Magento\Command;

use N98\Util\String;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use N98\Util\ArrayFunctions;

/**
 * Config consists of several parts which are merged.
 * The configuration which is global (not Magento project specific) is loaded
 * during construction.
 *
 * As soon as the Magento folder is known, loadStageTwo should be called.
 *
 * The toArray method only works if the Magento folder specific configuration is already loaded.
 *
 * Class ConfigurationLoader
 * @package N98\Magento\Command
 */
class ConfigurationLoader
{
    /**
     * Config passed in the constructor
     *
     * @var array
     */
    protected $_initialConfig;

    /**
     * @var array
     */
    protected $_configArray = null;

    /**
     * Cache
     *
     * @var array
     */
    protected $_distConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_pluginConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_systemConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_userConfig;

    /**
     * Cache
     *
     * @var array
     */
    protected $_projectConfig;

    /**
     * @var string
     */
    protected $_customConfigFilename = 'n98-magerun.yaml';

    /**
     * @var bool
     */
    protected $_isPharMode = true;

    /**
     * Load config
     * If $magentoRootFolder is null, only non-project config is loaded
     *
     * @param array $config
     * @param bool  $isPharMode
     */
    public function __construct($config, $isPharMode)
    {
        $this->_initialConfig = $config;
        $this->_isPharMode = $isPharMode;
    }

    /**
     * @param bool $loadExternalConfig
     * @return array
     */
    public function getPartialConfig($loadExternalConfig = true)
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        if ($loadExternalConfig) {
            $config = $this->loadSystemConfig($config);
            $config = $this->loadUserConfig($config);
        }

        return $config;
    }

    /**
     * @param string $magentoRootFolder
     * @param bool   $loadExternalConfig
     */
    public function loadStageTwo($magentoRootFolder, $loadExternalConfig = true)
    {
        $config = $this->_initialConfig;
        $config = $this->loadDistConfig($config);
        if ($loadExternalConfig) {
            $config = $this->loadPluginConfig($config, $magentoRootFolder);
            $config = $this->loadSystemConfig($config);
            $config = $this->loadUserConfig($config, $magentoRootFolder);
            $config = $this->loadProjectConfig($magentoRootFolder, $config);
        }
        $this->_configArray = $config;
    }

    /**
     * @throws \ErrorException
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->_configArray == null) {
            throw new \ErrorException('Configuration not yet fully loaded');
        }

        return $this->_configArray;
    }

    /**
     * @param array $initConfig
     *
     * @return array
     */
    protected function loadDistConfig($initConfig)
    {
        if ($this->_distConfig == null) {
            $this->_distConfig = Yaml::parse(__DIR__ . '/../../../../config.yaml');
        }
        $config = ArrayFunctions::mergeArrays($this->_distConfig, $initConfig);

        return $config;
    }

    /**
     * Check if there is a global config file in /etc folder
     *
     * @param array $config
     *
     * @return array
     */
    public function loadSystemConfig($config)
    {
        if ($this->_systemConfig == null) {
            $systemWideConfigFile = '/etc/' . $this->_customConfigFilename;
            if ($systemWideConfigFile && file_exists($systemWideConfigFile)) {
                $this->_systemConfig = Yaml::parse($systemWideConfigFile);
            } else {
                $this->_systemConfig = array();
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_systemConfig);
        return $config;
    }

    /**
     * Load config from all installed bundles
     *
     * @param array  $config
     * @param string $magentoRootFolder
     *
     * @return array
     */
    public function loadPluginConfig($config, $magentoRootFolder)
    {
        if ($this->_pluginConfig == null) {
            $this->_pluginConfig = array();
            $moduleBaseFolders = array();
            $config['plugin']['folders'][] = getenv('HOME') . '/.n98-magerun/modules';
            $config['plugin']['folders'][] = $magentoRootFolder . '/lib/n98-magerun/modules';
            foreach ($config['plugin']['folders'] as $folder) {
                if (is_dir($folder)) {
                    $moduleBaseFolders[] = $folder;
                }
            }

            /**
             * Allow modules to be placed vendor folder if not in phar mode
             */
            if (!$this->_isPharMode) {
                if (is_dir($this->getVendorDir())) {
                    $finder = Finder::create();
                    $finder
                        ->files()
                        ->depth(2)
                        ->followLinks()
                        ->ignoreUnreadableDirs(true)
                        ->name('n98-magerun.yaml')
                        ->in($this->getVendorDir());

                    foreach ($finder as $file) { /* @var $file \Symfony\Component\Finder\SplFileInfo */
                        $this->registerPluginConfigFile($magentoRootFolder, $file);
                    }
                }
            }

            if (count($moduleBaseFolders) > 0) {
                // Glob plugin folders
                $finder = Finder::create();
                $finder
                    ->files()
                    ->depth(1)
                    ->followLinks()
                    ->ignoreUnreadableDirs(true)
                    ->name('n98-magerun.yaml')
                    ->in($moduleBaseFolders);

                foreach ($finder as $file) { /* @var $file \Symfony\Component\Finder\SplFileInfo */
                    $this->registerPluginConfigFile($magentoRootFolder, $file);
                }
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_pluginConfig);

        return $config;
    }

    /**
     * @param string                                $rawConfig
     * @param string                                $magentoRootFolder
     * @param \Symfony\Component\Finder\SplFileInfo $file
     *
     * @return string
     */
    protected function applyVariables($rawConfig, $magentoRootFolder, $file = null)
    {
        $replace = array(
            '%module%' => $file ? $file->getPath() : '',
            '%root%'   => $magentoRootFolder,
        );

        return str_replace(array_keys($replace), $replace, $rawConfig);
    }


    /**
     * Check if there is a user config file. ~/.n98-magerun.yaml
     *
     * @param array  $config
     * @param string $magentoRootFolder
     *
     * @return array
     */
    public function loadUserConfig($config, $magentoRootFolder = null)
    {
        if ($this->_userConfig == null) {
            $this->_userConfig = array();
            $homeDirectory = getenv('HOME');
            $personalConfigFile = $homeDirectory . DIRECTORY_SEPARATOR . '.' . $this->_customConfigFilename;
            if ($homeDirectory && file_exists($personalConfigFile)) {
                $userConfig = $this->applyVariables(\file_get_contents($personalConfigFile), $magentoRootFolder, null);
                $this->_userConfig = Yaml::parse($userConfig);

                return $config;
            }
        }

        $config = ArrayFunctions::mergeArrays($config, $this->_userConfig);

        return $config;
    }

    /**
     * MAGENTO_ROOT/app/etc/n98-magerun.yaml
     *
     * @param string $magentoRootFolder
     * @param array $config
     *
     * @return array
     */
    public function loadProjectConfig($magentoRootFolder, $config)
    {
        if ($this->_projectConfig == null) {
            $this->_projectConfig = array();
            $projectConfigFile = $magentoRootFolder . DIRECTORY_SEPARATOR . 'app/etc/' . $this->_customConfigFilename;
            if ($projectConfigFile && file_exists($projectConfigFile)) {
                $projectConfig = $this->applyVariables(\file_get_contents($projectConfigFile), $magentoRootFolder, null);
                $this->_projectConfig = Yaml::parse($projectConfig);
            }

            $config = ArrayFunctions::mergeArrays($config, $this->_projectConfig);
        }

        return $config;
    }

    /**
     * Loads a plugin config file and merges it to plugin config
     *
     * @param string       $magentoRootFolder
     * @param SplFileInfo $file
     */
    protected function registerPluginConfigFile($magentoRootFolder, $file)
    {
        if (String::startsWith($file->getPathname(), 'vfs://')) {
            $path = $file->getPathname();
        } else {
            $path = $file->getRealPath();
        }
        $localPluginConfig = \file_get_contents($path);
        $localPluginConfig = Yaml::parse($this->applyVariables($localPluginConfig, $magentoRootFolder, $file));

        $this->_pluginConfig = ArrayFunctions::mergeArrays($this->_pluginConfig, $localPluginConfig);
    }

    /**
     * @return string
     */
    public function getVendorDir()
    {
        /* old vendor folder to give backward compatibility */
        $vendorFolder = $this->getConfigurationLoaderDir() . '/../../../../vendor';
        if (is_dir($vendorFolder)) {
            return $vendorFolder;
        }

        /* correct vendor folder for composer installations */
        $vendorFolder = $this->getConfigurationLoaderDir() . '/../../../../../../../vendor';
        if (is_dir($vendorFolder)) {
            return $vendorFolder;
        }

        return '';
    }

    /**
     * @return string
     */
    public function getConfigurationLoaderDir()
    {
        return __DIR__;
    }
}
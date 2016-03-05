<?php
/**
 * AutoLoad Class
 *
 * @category  Autoload class file
 * @package   AutoLoad
 * @author    xiang wu <yijianlingchen@outlook.com>
 * @copyright Copyright (c) 2016
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version   1.0.0
 */

namespace DbCompare\core;


class Autoload
{
    /**
     *
     * @var string
     */
    private $namespaceSeparator = '\\';
    /**
     *
     * @var string
     */
    private $fileExtension = '.class.php';
    /**
     *
     * @var array
     */
    private $namespaces = array();

    /**
     * Autoload constructor.
     */
    public function __construct()
    {

    }

    /**
     * Sets the namespace separator used by classes in the namespace of this class loader.
     *
     * @param string $separator
     * @return void
     */
    public function setNamespaceSeparator($separator)
    {
        $this->namespaceSeparator = $separator;
    }

    /**
     * Gets the namespace separator used by classes in the namespace of this class loader.
     *
     * @return string $namespaceSeparator.
     */
    public function getNamespaceSeparator()
    {
        return $this->namespaceSeparator;
    }

    /**
     * Sets the file extension of class files in the namespace of this class loader.
     *
     * @param string $fileExtension
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * Gets the file extension of class files in the namespace of this class loader.
     *
     * @return string $fileExtension
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * Gets all currently registered namespaces with associated include paths
     *
     * @return array $namespaces
     */
    public function getRegisteredNamespaces()
    {
        return $this->namespaces;
    }

    /*
     * Set the include path for a namespace and register the namespace in the class lookup
     *
     * @param string $ns
     * @param string $includePath
     * @return void
     */
    public function registerNamespace($namespace, $includePath = null)
    {
        $this->namespaces[$namespace] = $includePath;
    }

    /**
     * Set the include path for multiple namespaces and register the namespace in the
     * class lookup
     *
     * @param array $registrations
     * @return void
     */
    public function registerNamespaces(array $registrations)
    {
        foreach ($registrations as $namespace => $includePath) {
            $this->registerNamespace($namespace, $includePath);
        }
    }

    /**
     * Installs this class loader on the SPL autoload stack.
     * Remove from autoloader stack with {@link unregister()}
     *
     * @see spl_autoload_register()
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     *
     * @see spl_autoload_unregister()
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $className
     * @return void
     */
    public function loadClass($className)
    {
        $fileName = '';
        foreach ($this->namespaces as $namespace => $includePath) {
            if ($namespace.$this->namespaceSeparator == substr($className, 0, strlen($namespace.$this->namespaceSeparator))) {
                if (false !== ($lastNsPos = strripos($className, $this->namespaceSeparator))) {
                    $className = substr($className, $lastNsPos + 1);
                }
                $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->fileExtension;
                $path = ($includePath !== null ? $includePath . DIRECTORY_SEPARATOR : __DIR__ . DIRECTORY_SEPARATOR) . $fileName;
                $path = str_replace('/', '\\', $path);
                $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
                if (file_exists($path)) {
                    require $path;
                }
            }
        }
    }
}
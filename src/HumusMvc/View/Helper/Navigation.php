<?php

namespace HumusMvc\View\Helper;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend_Navigation as ZendNavigation;
use Zend_View_Exception as ViewException;
use Zend_View_Helper_Navigation as NavigationViewHelper;
use Zend_View_Helper_Navigation_Helper as NavigationViewHelperHelper;

/**
 * @category Humus
 * @package HumusMvc
 * @subpacke View
 */
class Navigation extends NavigationViewHelper implements ServiceLocatorAwareInterface
{
    /**
     * @var Navigation\PluginManager
     */
    protected $plugins;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Set manager for retrieving navigation helpers
     *
     * @param  Navigation\PluginManager $plugins
     * @return Navigation
     */
    public function setPluginManager(Navigation\PluginManager $plugins)
    {
        if ($this->view) {
            $plugins->setView($this->view);
        }
        $this->plugins = $plugins;
        return $this;
    }

    /**
     * Retrieve plugin loader for navigation helpers
     *
     * Lazy-loads an instance of Navigation\HelperLoader if none currently
     * registered.
     *
     * @return Navigation\PluginManager
     */
    public function getPluginManager()
    {
        if (null === $this->plugins) {
            $this->setPluginManager(new Navigation\PluginManager());
        }
        return $this->plugins;
    }

    /**
     * Set the service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Navigation
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * Get the service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Get container
     *
     * @return ZendNavigation|\Zend_Navigation_Container
     */
    public function getContainer()
    {
        if (null === $this->_container) {
            // try to fetch from service locator first
            $serviceLocator = $this->getServiceLocator();
            if ($serviceLocator->has('Navigation')) {
                $this->_container = $serviceLocator->get('Navigation');
            } else {
                // nothing found in service locator, create new container
                $this->_container = new ZendNavigation();
            }
        }

        return $this->_container;
    }

    /**
     * Get translator
     *
     * @return null|\Zend_Translate_Adapter
     */
    public function getTranslator()
    {
        if (null === $this->_translator) {
            $serviceLocator = $this->getServiceLocator();
            if ($serviceLocator->has('Translator')) {
                $this->setTranslator($serviceLocator->get('Translator'));
            }
        }

        return $this->_translator;
    }

    /**
     * Get ACL
     *
     * @return\ Zend_Acl|null  ACL object or null
     */
    public function getAcl()
    {
        if ($this->_acl === null) {
            $serviceLocator = $this->getServiceLocator();
            // try service locator first
            if ($serviceLocator->has('Acl')) {
                $this->setAcl($serviceLocator->get('Acl'));
            // try default acl object
            } elseif (self::$_defaultAcl !== null) {
                return self::$_defaultAcl;

            }
        }

        return $this->_acl;
    }

    /**
     * Get role
     *
     * @return null|string|\Zend_Acl_Role_Interface
     */
    public function getRole()
    {
        if ($this->_role === null) {
            $serviceLocator = $this->getServiceLocator();
            // try service locator first
            if ($serviceLocator->has('AclRole')) {
                $this->setRole($serviceLocator->get('AclRole'));
                // try default acl object
            } elseif (self::$_defaultRole !== null) {
                return self::$_defaultRole;

            }
        }

        return $this->_role;
    }

    /**
     * Find helper
     *
     * @param string $proxy
     * @param bool $strict
     * @return null|NavigationViewHelperHelper
     * @throws ViewException
     */
    public function findHelper($proxy, $strict = true)
    {
        if (isset($this->_helpers[$proxy])) {
            return $this->_helpers[$proxy];
        }

        if (!$strict) {
            if (!$this->getPluginManager()->has($proxy)) {
                return null;
            }
        }
        $helper = $this->getPluginManager()->get($proxy);

        if (!$helper instanceof NavigationViewHelperHelper) {
            if ($strict) {
                $e = new ViewException(sprintf(
                    'Proxy helper "%s" is not an instance of ' .
                        'Zend_View_Helper_Navigation_Helper',
                    get_class($helper)));
                $e->setView($this->view);
                throw $e;
            }

            return null;
        }

        $this->_inject($helper);
        $this->_helpers[$proxy] = $helper;

        return $helper;
    }
}

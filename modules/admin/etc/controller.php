<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\StringMethods;

/**
 * Description of Controller
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    private $_security;
    
    /**
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $string = StringMethods::removeDiacriticalMarks($string);
        $string = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|', ' '), '-', $string);
        $string = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $string);
        $string = trim($string);
        $string = trim($string, '-');
        return strtolower($string);
    }

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $lastActive = $session->get('lastActive');
        $user = $this->getUser();

        if (!$user) {
            self::redirect('/admin/login');
        }

        if ($lastActive > time() - 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            $this->_security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _publisher()
    {
        if ($this->_security->getUser() && !$this->_security->isGranted('role_publisher')) {
            $view = $this->getActionView();
            $view->infoMessage('Access denied! Publisher access level required.');
            $this->_security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _admin()
    {
        if ($this->_security->getUser() && !$this->_security->isGranted('role_admin')) {
            $view = $this->getActionView();
            $view->infoMessage('Access denied! Administrator access level required.');
            $this->_security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        if ($this->_security->getUser() && !$this->_security->isGranted('role_superadmin')) {
            $view = $this->getActionView();
            $view->infoMessage('Access denied! Super admin access level required.');
            $this->_security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isSuperAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        $this->_security = Registry::get('security');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });
    }

    /**
     * 
     */
    public function checkToken()
    {
        $session = Registry::get('session');
        //$this->_security = Registry::get('security');
        $view = $this->getActionView();

        if (base64_decode(RequestMethods::post('tk')) !== $session->get('csrftoken')) {
            $view->errorMessage('Security token is not valid');
            //$this->_security->logout();
            self::redirect('/admin/');
        }
    }

    /**
     * 
     * @return boolean
     */
    public function checkTokenAjax()
    {
        $session = Registry::get('session');

        if (base64_decode(RequestMethods::post('tk')) === $session->get('csrftoken')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * load user from security context
     */
    public function getUser()
    {
        $security = Registry::get('security');
        $user = $security->getUser();

        return $user;
    }

    /**
     * 
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $user = $this->getUser();

        if ($view) {
            $view->set('authUser', $this->getUser());

            if ($user) {
                $view->set('isAdmin', $this->isAdmin())
                        ->set('isSuperAdmin', $this->isSuperAdmin())
                        ->set('token', $this->_security->getCsrfToken());
            }
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getUser());

            if ($user) {
                $layoutView->set('isAdmin', $this->isAdmin())
                        ->set('isSuperAdmin', $this->isSuperAdmin())
                        ->set('token', $this->_security->getCsrfToken());
            }
        }

        parent::render();
    }

}

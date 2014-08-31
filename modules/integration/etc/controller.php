<?php

namespace Integration\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;

/**
 * Description of Controller
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    private $_security;
    
    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $user = $this->getUser();

        if (!$user) {
            self::redirect('/login');
        }

        if ($session->get('lastActive') > time() - 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * @protected
     */
    public function _cron()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];

        if (!preg_match('/curl/i', $u_agent)) {
            exit();
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        $view = $this->getActionView();

        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') !== true) {
            $view->infoMessage('Access denied');
            $this->_security->logout();
            self::redirect('/login');
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
     * load user from security context
     */
    public function getUser()
    {
        return $this->_security->getUser();
    }

    /**
     * 
     */
    public function render()
    {
        if ($this->getUser()) {
            if ($this->getActionView()) {
                $this->getActionView()
                        ->set('authUser', $this->getUser());
            }

            if ($this->getLayoutView()) {
                $this->getLayoutView()
                        ->set('authUser', $this->getUser());
            }
        }

        parent::render();
    }

}

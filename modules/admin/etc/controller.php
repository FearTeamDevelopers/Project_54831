<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;

/**
 * Description of Controller
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $security = Registry::get('security');
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
            $security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _publisher()
    {
        $security = Registry::get('security');

        if ($security->getUser() && !$security->isGranted('role_publisher')) {
            $view = $this->getActionView();
            $view->infoMessage('Access denied! Publisher access level required.');
            $security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _admin()
    {
        $security = Registry::get('security');
        
        if ($security->getUser() && !$security->isGranted('role_admin')) {
            $view = $this->getActionView();
            $view->infoMessage('Access denied! Administrator access level required.');
            $security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        $security = Registry::get('security');

        if ($security->getUser() && !$security->isGranted('role_superadmin')) {
            $view = $this->getActionView();
            $view->infoMessage('Access denied! Super admin access level required.');
            $security->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $database = Registry::get('database');
        $database->connect();

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
        //$security = Registry::get('security');
        $view = $this->getActionView();

        if (base64_decode(RequestMethods::post('tk')) !== $session->get('csrftoken')) {
            $view->errorMessage('Security token is not valid');
            //$security->logout();
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
        }else{
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
        $security = Registry::get('security');
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $user = $this->getUser();
        
        if ($view) {
            $view->set('authUser', $this->getUser());

            if ($user) {
                $view->set('isAdmin', $security->isGranted('role_admin'))
                        ->set('isSuperAdmin', $security->isGranted('role_superadmin'))
                        ->set('token', $security->getCsrfToken());
            }
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getUser());

            if ($user) {
                $layoutView->set('isAdmin', $security->isGranted('role_admin'))
                        ->set('isSuperAdmin', $security->isGranted('role_superadmin'))
                        ->set('token', $security->getCsrfToken());
            }
        }

        parent::render();
    }

}

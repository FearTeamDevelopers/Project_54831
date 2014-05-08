<?php

namespace Cron\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\Controller as BaseController;

/**
 * Description of Controller
 *
 * @author Tomy
 */
class Controller extends BaseController {

    protected static $_imageExtensions = array('gif', 'jpg', 'png', 'jpeg');

    /**
     * @protected
     */
    public function _secured() {
        $session = Registry::get("session");
        $security = Registry::get("security");
        $lastActive = $session->get("lastActive");

        $user = $this->getUser();

        if (!$user) {
            self::redirect("/login");
        }

        if ($lastActive > time() - 600) {
            $session->set("lastActive", time());
        } else {
            $view = $this->getActionView();

            $view->flashMessage("You has been logged out for long inactivity");
            $security->logout();
            self::redirect("/login");
        }
    }

    /**
     * @protected
     */
    public function _cron() {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];

        if(!preg_match("/curl/i", $u_agent)) {
            exit();
        }
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array()) {
        parent::__construct($options);

        $database = Registry::get("database");
        $database->connect();

        // schedule disconnect from database 
        Events::add("framework.controller.destruct.after", function($name) {
                    $database = Registry::get("database");
                    $database->disconnect();
                });
    }

    /**
     * load user from security context
     */
    public function getUser() {
        $security = Registry::get("security");
        $user = $security->getUser();

        return $user;
    }

    /**
     * 
     */
    public function render() {
        $this->setWillRenderLayoutView(false);
        $this->setWillRenderActionView(false);

        parent::render();
    }

}
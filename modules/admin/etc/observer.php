<?php

use THCFrame\Registry\Registry as Registry;

/**
 * 
 */
class Admin_Etc_Observer
{

    /**
     * 
     * @param array $params
     */
    public function adminLog($params = array())
    {
        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $security = Registry::get('security');
        $userId = $security->getUser()->getId();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $paramStr = join(', ', $params);
        } else {
            $paramStr = '';
        }

        $log = new Admin_Model_AdminLog(array(
            'userId' => $userId,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'params' => $paramStr
        ));

        if ($log->validate()) {
            $log->save();
        }
    }

}

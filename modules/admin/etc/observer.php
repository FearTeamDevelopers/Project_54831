<?php

use THCFrame\Registry\Registry;

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
        $userId = $security->getUser()->getWholeName();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);
            $paramStr = join(', ', $params);
        } else {
            $result = 'fail';
            $paramStr = '';
        }

        $log = new Admin_Model_AdminLog(array(
            'userId' => $userId,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'result' => $result,
            'params' => $paramStr
        ));

        if ($log->validate()) {
            $log->save();
        }
    }

}

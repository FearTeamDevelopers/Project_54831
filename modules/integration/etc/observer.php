<?php

use THCFrame\Registry\Registry;

/**
 * 
 */
class Integration_Etc_Observer
{

    /**
     * 
     * @param array $params
     */
    public function cronLog($params = array())
    {
        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $paramStr = join(', ', $params);
        } else {
            $paramStr = '';
        }

        $log = new Admin_Model_AdminLog(array(
            'userId' => 'cron',
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

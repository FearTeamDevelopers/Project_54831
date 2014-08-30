<?php

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;

/**
 * 
 */
class Integration_Etc_Observer implements SubscriberInterface
{

    /**
     * 
     * @return type
     */
    public function getSubscribedEvents()
    {
        return array(
            'cron.log' => 'cronLog'
        );
    }
    
    /**
     * 
     * @param array $params
     */
    public function cronLog($params = array())
    {
        $params = func_get_args();
        
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

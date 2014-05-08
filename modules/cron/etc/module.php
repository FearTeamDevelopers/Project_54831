<?php

use THCFrame\Module\Module as Module;

/**
 * Description of Module
 *
 * @author Tomy
 */
class Cron_Etc_Module extends Module{

    /**
     * @read
     */
    protected $_moduleName = "Cron";
    
    protected $_routes = array(
        array(
            'pattern' => '/mailtest',
            'module' => 'cron',
            'controller' => 'notification',
            'action' => 'mailTest',
        ),
        array(
            'pattern' => '/lan',
            'module' => 'cron',
            'controller' => 'notification',
            'action' => 'lowAttendNotif',
        )
    );
}
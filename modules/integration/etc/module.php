<?php

use THCFrame\Module\Module as Module;

/**
 * Description of Integration_Etc_Module
 *
 * @author Tomy
 */
class Integration_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Integration';

    /**
     * @read
     */
    protected $_observerClass = 'Integration_Etc_Observer';
    protected $_routes = array(
        
    );

}

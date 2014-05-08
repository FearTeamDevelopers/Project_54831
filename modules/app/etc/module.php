<?php

use THCFrame\Module\Module as Module;

/**
 * Description of Module
 *
 * @author Tomy
 */
class App_Etc_Module extends Module{

    /**
     * @read
     */
    protected $_moduleName = 'App';
    
    /**
     * @read
     */
    protected $_observerClass = 'App_Etc_Observer';
    
    protected $_routes = array(
        array(
            'pattern' => '/bio',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'bio',
        ),
        array(
            'pattern' => '/news',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
        ),
        array(
            'pattern' => '/news/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
            'args' => 'page'
        ),
        array(
            'pattern' => '/news/detail/:title',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'detail',
            'args' => 'title'
        ),
        array(
            'pattern' => '/design',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'design',
        ),
        array(
            'pattern' => '/styling',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'styling',
        ),
        array(
            'pattern' => '/partners',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'partners',
        ),
        array(
            'pattern' => '/contact',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'contact',
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        )
    );
}
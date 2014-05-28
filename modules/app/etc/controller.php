<?php

namespace App\Etc;

use THCFrame\Events\Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;

/**
 * Description of Controller
 *
 * @author Tomy
 */
class Controller extends BaseController
{

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
     * @param type $sections
     * @param type $order
     * @param type $direction
     * @return boolean
     */
    public function getSectionsByParentId($parentId = null)
    {
        if (null === $parentId) {
            return false;
        } else {

            $cache = Registry::get('cache');
            $cacheKey = 'section_' . $parentId;
            $sect = $cache->get($cacheKey);

            if (null !== $sect) {
                return $sect;
            } else {
                $sect = \App_Model_Section::all(
                                array(
                            'parentId = ?' => $parentId,
                            'active = ?' => true
                                )
                        );

                if (NULL !== $sect) {
                    $cache->set($cacheKey, $sect);
                    return $sect;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * 
     * @param type $section
     * @param type $title
     * @param type $order
     * @param type $direction
     * @return boolean
     */
    public function getSection($urlKey = null)
    {
        if (null === $urlKey) {
            return false;
        } else {
            $cache = Registry::get('cache');
            $cacheKey = 'section_' . $urlKey;
            $sect = $cache->get($cacheKey);

            if (null !== $sect) {
                return $sect;
            } else {
                $sect = \App_Model_Section::first(
                                array(
                            'urlKey = ?' => $urlKey,
                            'active = ?' => true
                                )
                        );

                if (NULL !== $sect) {
                    $cache->set($cacheKey, $sect);
                    return $sect;
                } else {
                    return false;
                }
            }
        }
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

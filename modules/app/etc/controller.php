<?php

namespace App\Etc;

use THCFrame\Events\Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;

/**
 * Module specific controller class extending framework controller class
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

        $cache = Registry::get('cache');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });
        
        $metaData = $cache->get('global_meta_data');

        if (NULL !== $metaData) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metakeywords' => $this->loadConfigFromDb('meta_keywords'),
                'metadescription' => $this->loadConfigFromDb('meta_description'),
                'metarobots' => $this->loadConfigFromDb('meta_robots'),
            );

            $cache->set('global_meta_data', $metaData);
        }
        
        $this->getLayoutView()
                ->set('metatitle', 'Marko.in')
                ->set('metakeywords', $metaData['metakeywords'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metadescription', $metaData['metadescription']);
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
     * @param type $newVar
     */
    protected function checkRefferer($newVar){
        $view = $this->getActionView();

        if ($view->getHttpReferer() === null) {
            $this->willRenderLayoutView = true;
            $layoutView = $this->getLayoutView();
            $layoutView->set('hidetop', true)
                    ->set('showaction', true)
                    ->set($newVar, true);
        } else {
            $this->willRenderLayoutView = false;
        }
    }
    
    /**
     * 
     */
    public function render()
    {
        parent::render();
    }

}

<?php

namespace App\Etc;

use THCFrame\Events\Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;

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
        
        $lastAnnouncement = \App_Model_Announcement::first(
                        array(
                            'active = ?' => true,
                            'dateStart < ?' => date('Y-m-d H:i:s'),
                            'dateEnd > ?' => date('Y-m-d H:i:s'),
                        )
        );

        $npp = (int) $this->loadConfigFromDb('news_per_page');

        $newsCount = \App_Model_News::count(
                        array('active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );

        $newsPageCount = ceil($newsCount / $npp);
        $maxPageCount = (int) $this->loadConfigFromDb('news_max_page_count');

        if ((int) $newsPageCount > $maxPageCount) {
            $newsPageCount = $maxPageCount;
        }

        $metaData = $cache->get('global_meta_data');

        if (NULL !== $metaData) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metakeywords' => $this->loadConfigFromDb('meta_keywords'),
                'metadescription' => $this->loadConfigFromDb('meta_description'),
                'metarobots' => $this->loadConfigFromDb('meta_robots'),
                'metatitle' => $this->loadConfigFromDb('meta_title'),
                'metaogurl' => $this->loadConfigFromDb('meta_og_url'),
                'metaogtype' => $this->loadConfigFromDb('meta_og_type'),
                'metaogimage' => $this->loadConfigFromDb('meta_og_image'),
                'metaogsitename' => $this->loadConfigFromDb('meta_og_site_name')
            );

            $cache->set('global_meta_data', $metaData);
        }
        
        $this->getLayoutView()
                ->set('metatitle', $metaData['metatitle'])
                ->set('metakeywords', $metaData['metakeywords'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metadescription', $metaData['metadescription'])
                ->set('metaogurl', $metaData['metaogurl'])
                ->set('metaogtype', $metaData['metaogtype'])
                ->set('metaogimage', $metaData['metaogimage'])
                ->set('metaogsitename', $metaData['metaogsitename'])
                ->set('newspagecount', $newsPageCount)
                ->set('announcement', $lastAnnouncement);
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
    protected function checkRefferer($newVar)
    {
        $view = $this->getActionView();
        $host = RequestMethods::server('SERVER_NAME');

        if ($view->getHttpReferer() === null || !preg_match('#^http:\/\/'.$host.'#', $view->getHttpReferer())) {
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
        $this->getLayoutView()
                ->set('env', ENV);
                
        parent::render();
    }

}

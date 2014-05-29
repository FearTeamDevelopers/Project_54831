<?php

use Admin\Etc\Controller;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_Index extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $latestNews = App_Model_News::all(
                        array(), 
                array('id', 'active', 'urlKey', 'title', 'shortBody', 'created'), 
                array('created' => 'desc'), 3
        );

        $collectionList = App_Model_Collection::all(
                        array(), 
                array('id', 'title', 'created', 'date', 'photographer')
        );
        
        $partnerList = App_Model_Partner::all(
                        array(),
                array('id', 'title', 'logo', 'web')
        );
        
        $activeAnnouncements = App_Model_Announcement::all(
                        array('active = ?' => true), 
                array('id', 'title', 'created', 'body')
        );
        
        $view->set('latestnews', $latestNews)
                ->set('collectionlist', $collectionList)
                ->set('partnerlist', $partnerList)
                ->set('activeannounc', $activeAnnouncements);
    }

}

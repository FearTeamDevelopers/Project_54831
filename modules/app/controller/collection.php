<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;

/**
 * Description of App_Controller_Collection
 *
 * @author Tomy
 */
class App_Controller_Collection extends Controller
{

    /**
     * 
     * @param type $urlKey
     * @return type
     */
    protected function _getContent($urlKey)
    {
        $cache = Registry::get('cache');

        $collectionCache = $cache->get('cache_collection_' . $urlKey);

        if (NULL !== $collectionCache) {
            $collections = $collectionCache;
            return $collections;
        } else {
            $collectionQuery = App_Model_Collection::getQuery(array('cl.*'))
                    ->join('tb_collectionmenu', 'cl.menuId = clm.id', 'clm', 
                            array('clm.urlKey',))
                    ->where('clm.urlKey = ?', $urlKey)
                    ->where('cl.active = ?', true)
                    ->order('cl.created', 'DESC');

            $collections = App_Model_Collection::initialize($collectionQuery);

            if (NULL !== $collections) {
                foreach ($collections as $collection) {
                    $query = App_Model_Photo::getQuery(array('ph.*'));
                    $query->join('tb_collectionphoto', 'ph.id = clp.photoId', 'clp', 
                            array('clp.photoId', 'clp.collectionId'))
                            ->where('clp.collectionId = ?', $collection->getId())
                            ->where('ph.active = ?', true);

                    if ($urlKey == 'portfolio') {
                        $query->order('ph.photoName', 'ASC');
                    } else {
                        $query->order('ph.priority', 'DESC')
                                ->order('ph.created', 'DESC');
                    }
                    $photos = App_Model_Photo::initialize($query);

                    if($photos === null){
                        $photos = array();
                    }
                    
                    $videoQuery = App_Model_Video::getQuery(array('vi.*'));
                    $videoQuery->join('tb_collectionvideo', 'vi.id = clv.videoId', 'clv', 
                            array('clv.videoId', 'clv.collectionId'))
                            ->where('clv.collectionId = ?', $collection->getId())
                            ->where('vi.active = ?', true)
                            ->order('vi.priority', 'DESC')
                            ->order('vi.created', 'DESC');

                    $videos = App_Model_Video::initialize($videoQuery);
                    
                    if($videos === null){
                        $videos = array();
                    }
                    
                    $collection->videos = $videos;
                    $collection->photos = $photos;
                }

                $cache->set('cache_collection_' . $urlKey, $collections);
            }
            return $collections;
        }
    }

    /**
     * 
     * @param type $urlKey
     */
    public function show($urlKey)
    {
        $view = $this->getActionView();
        
        if($view->getHttpReferer() === null){
            $this->willRenderLayoutView = true;
        }else{
            $this->willRenderLayoutView = false;
        }
        
        $this->willRenderActionView = true;

        $content = $this->_getContent($urlKey);

        $view->set('collections', $content);
    }
}

<?php

use App\Etc\Controller as Controller;
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
        } else {
            $collectionQuery = App_Model_Collection::getQuery(array('cl.*'));
            $collectionQuery->join('tb_collectionmenu', 'cl.menuId = clm.id', 'clm', 
                    array('clm.urlKey',))
                    ->where('clm.urlKey = ?', $urlKey)
                    ->where('cl.active = ?', true)
                    ->order('cl.created', 'DESC');

            $collections = App_Model_Collection::initialize($collectionQuery);

            if (NULL !== $collections) {
                foreach ($collections as $collection) {
                    $query = App_Model_Photo::getQuery(array('ph.*'));
                    $query->join('tb_collectionphoto', 'ph.id = clp.photoId', 'clp', 
                            array('clp.*'))
                            ->where('clp.collectionId = ?', $collection->getId())
                            ->where('ph.active = ?', true);

                    if ($urlKey == 'portfolio') {
                        $query->order('ph.photoName', 'ASC');
                    } else {
                        $query->order('ph.priority', 'DESC')
                                ->order('ph.created', 'DESC');
                    }
                    $photos = App_Model_Photo::initialize($query);

                    $videoQuery = App_Model_Video::getQuery(array('vi.*'));
                    $videoQuery->join('tb_collectionvideo', 'vi.id = clv.videoId', 'clv', 
                            array('clv.*'))
                            ->where('clv.collectionId = ?', $collection->getId())
                            ->where('vi.active = ?', true)
                            ->order('vi.priority', 'DESC')
                            ->order('vi.created', 'DESC');

                    $videos = App_Model_Video::initialize($videoQuery);

                    $collection->videos = $videos;
                    $collection->photos = $photos;
                }
            }
            $cache->set('cache_collection_' . $urlKey, $collections);
        }

        return $collection;
    }

    /**
     * 
     * @param type $id
     */
    public function show($urlKey)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = true;

        $view = $this->getActionView();

        $content = $this->_getContent($urlKey);
        
        if(is_array($content)){
            $collections = $content;
        }else{
            $collections[] = $content;
        }
        
        $view->set('collections', $collections);
    }

}

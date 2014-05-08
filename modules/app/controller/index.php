<?php

use App\Etc\Controller as Controller;
use THCFrame\Registry\Registry;

/**
 * Description of App_Controller_Index
 *
 * @author Tomy
 */
class App_Controller_Index extends Controller
{

    /**
     * 
     * @param type $urlKey
     * @return type
     */
    protected function _getSectionContent($urlKey)
    {
        $cache = Registry::get('cache');

        $content = $cache->get('cache_section_' . $urlKey);

        if (NULL !== $content) {
            return $content;
        } else {
            $section = $this->getSection($urlKey);

            $contentText = App_Model_PageContent::first(
                            array(
                        'sectionId = ?' => $section->getId(),
                        'active = ?' => true
                            ), array('pageName', 'body')
            );

            if ($section->getSupportPhoto()) {
                $query = App_Model_Photo::getQuery(array('ph.*'))
                        ->join('tb_photosection', 'ph.id = phs.photoId', 'phs', 
                                array('phs.photoId', 'phs.sectionId'))
                        ->where('phs.sectionId = ?', $section->getId())
                        ->order('ph.priority', 'DESC')
                        ->order('ph.created', 'DESC');

                $photosResult = App_Model_Photo::initialize($query);

                if (is_array($photosResult)) {
                    $photos = $photosResult;
                } else {
                    $photos[] = $photosResult;
                }
            }

            if ($section->getSupportVideo()) {
                $queryVi = App_Model_Video::getQuery(array('vi.*'))
                        ->join('tb_videosection', 'vi.id = vis.videoId', 'vis', 
                                array('vis.videoId', 'vis.sectionId'))
                        ->where('vis.sectionId = ?', $section->getId())
                        ->order('vi.priority', 'DESC')
                        ->order('vi.created', 'DESC');

                $videosResult = App_Model_Video::initialize($queryVi);

                if (is_array($videosResult)) {
                    $videos = $videosResult;
                } else {
                    $videos[] = $videosResult;
                }
            }

            if ($section->getSupportCollection()) {
                $collectionList = App_Model_CollectionMenu::all(
                        array(
                            'sectionId = ?' => $section->getId(),
                            'active = ?' => true
                                ), 
                        array('*'), 
                        array('rank' => 'asc', 'created' => 'desc'));
            }

            $content = array(
                'text' => $contentText,
                'photos' => $photos,
                'videos' => $videos,
                'collectionlist' => $collectionList
            );

            $cache->set('cache_section_' . $urlKey, $content);

            return $content;
        }
    }

    /**
     * Index method of Index controller display lists of projects
     */
    public function index()
    {
        $view = $this->getActionView();

        $lastAnnouncement = App_Model_Announcement::first(
                        array(
                            'active = ?' => true,
                            'dateStart < ?' => date('Y-m-d H:i:s'),
                            'dateEnd > ?' => date('Y-m-d H:i:s'),
                            )
        );

        $view->set('lastannouncement', $lastAnnouncement);
    }

    /**
     * 
     */
    public function bio()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = true;

        $view = $this->getActionView();

        $content = $this->_getSectionContent('bio');

        $contentText = $content['text'];
        $photos = $content['photos'];
        $videos = $content['videos'];
        $collections = $content['collectionlist'];

        $view->set('text', $contentText)
                ->set('photos', $photos)
                ->set('videos', $videos)
                ->set('collectionlist', $collections);
    }

    /**
     * 
     */
    public function design()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = true;

        $view = $this->getActionView();

        $content = $this->_getSectionContent('design');

        $contentText = $content['text'];
        $photos = $content['photos'];
        $videos = $content['videos'];
        $collections = $content['collectionlist'];

        $view->set('text', $contentText)
                ->set('photos', $photos)
                ->set('videos', $videos)
                ->set('collectionlist', $collections);
    }

    /**
     * 
     */
    public function styling()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = true;

        $view = $this->getActionView();

        $content = $this->_getSectionContent('styling');

        $contentText = $content['text'];
        $photos = $content['photos'];
        $videos = $content['videos'];
        $collections = $content['collectionlist'];

        $view->set('text', $contentText)
                ->set('photos', $photos)
                ->set('videos', $videos)
                ->set('collectionlist', $collections);
    }

    /**
     * 
     */
    public function contact()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = true;

        $view = $this->getActionView();

        $content = $this->_getSectionContent('contact');

        $contentText = $content['text'];
        $photos = $content['photos'];
        $videos = $content['videos'];
        $collections = $content['collectionlist'];

        $view->set('text', $contentText)
                ->set('photos', $photos)
                ->set('videos', $videos)
                ->set('collectionlist', $collections);
    }

    /**
     * 
     */
    public function partners()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = true;

        $view = $this->getActionView();

        $partnerSections = $this->getSectionsByParentId(6);

        if (NULL !== $partnerSections) {
            $sec = array();
            foreach ($partnerSections as $section) {
                $sec[$section->title] = App_Model_Partner::all(
                                array(
                            'sectionId = ?' => $section->id,
                            'active = ?' => true
                                ), array('title', 'logo', 'web')
                );
            }
        }

        $view->set('partners', $sec)
                ->set('sections', $partnerSections);
    }

}

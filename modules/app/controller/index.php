<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Rss\Rss;
use THCFrame\Request\RequestMethods;

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
            $videos = $photos = array();

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

                $photos = App_Model_Photo::initialize($query);
            }

            if ($section->getSupportVideo()) {
                $queryVi = App_Model_Video::getQuery(array('vi.*'))
                        ->join('tb_videosection', 'vi.id = vis.videoId', 'vis', 
                                array('vis.videoId', 'vis.sectionId'))
                        ->where('vis.sectionId = ?', $section->getId())
                        ->order('vi.priority', 'DESC')
                        ->order('vi.created', 'DESC');

                $videos = App_Model_Video::initialize($queryVi);
            }

            $collectionList = '';
            if ($section->getSupportCollection()) {
                $collectionList = App_Model_CollectionMenu::all(
                                array(
                            'sectionId = ?' => $section->getId(),
                            'active = ?' => true
                                ), array('*'), array('rank' => 'asc', 'created' => 'desc'));
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
        $layoutView = $this->getLayoutView();

        $lastAnnouncement = App_Model_Announcement::first(
                        array(
                            'active = ?' => true,
                            'dateStart < ?' => date('Y-m-d H:i:s'),
                            'dateEnd > ?' => date('Y-m-d H:i:s'),
                        )
        );

        $npp = (int) $this->loadConfigFromDb('news_per_page');

        $newsCount = App_Model_News::count(
                        array('active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );

        $newsPageCount = ceil($newsCount / $npp);
        $maxPageCount = (int) $this->loadConfigFromDb('news_max_page_count');

        if ((int) $newsPageCount > $maxPageCount) {
            $newsPageCount = $maxPageCount;
        }

        $view->set('lastannouncement', $lastAnnouncement);
        $layoutView->set('newspagecount', $newsPageCount);
    }

    /**
     * Load and show content of bio section
     */
    public function bio()
    {
        $view = $this->getActionView();
        $this->checkRefferer('showbio');
        $this->willRenderActionView = true;

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
    public function provas()
    {
        $view = $this->getActionView();
        $this->checkRefferer('showcust');
        $this->willRenderActionView = true;

        $content = $this->_getSectionContent('provas');

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
     * Load and show content of design section
     */
    public function design()
    {
        $view = $this->getActionView();
        $this->checkRefferer('showdesign');
        $this->willRenderActionView = true;

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
     * Load and show content of styling section
     */
    public function styling()
    {
        $view = $this->getActionView();
        $this->checkRefferer('showstyling');
        $this->willRenderActionView = true;

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
     * Load and show content of contact section
     */
    public function contact()
    {
        $view = $this->getActionView();
        $this->checkRefferer('showcontact');
        $this->willRenderActionView = true;

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
     * Load and show content of all partner sections
     */
    public function partners()
    {
        $view = $this->getActionView();
        $this->checkRefferer('showpartners');
        $this->willRenderActionView = true;

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

    /**
     * Creates rss feed xml from news
     */
    public function feed()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        $rss = new Rss(array(
            'title' => $this->loadConfigFromDb('feed_title'),
            'link' => $this->loadConfigFromDb('feed_link'),
            'description' => $this->loadConfigFromDb('feed_description'),
            'language' => $this->loadConfigFromDb('feed_language'),
            'imageUrl' => $this->loadConfigFromDb('feed_image_url'),
            'imageLink' => $this->loadConfigFromDb('feed_image_link'),
            'imageTitle' => $this->loadConfigFromDb('feed_image_title'),
            'imageWidth' => $this->loadConfigFromDb('feed_image_width'),
            'imageHeight' => $this->loadConfigFromDb('feed_image_height')
        ));

        $news = App_Model_News::all(
                        array('active = ?' => true,
                    'rssFeedBody <> ?' => '',
                    'expirationDate >= ?' => date('Y-m-d H:i:s')), 
                array('urlKey', 'title', 'rssFeedBody'), 
                array('created' => 'desc'), 10
        );

        foreach ($news as $nws) {
            $link = 'http://' . RequestMethods::server('HTTP_HOST') . '/news/detail/' . $nws->getUrlKey();
            $rss->addItem($nws->getTitle(), $link, $nws->getRssFeedBody());
        }

        header("Content-Type: application/xml; charset=UTF-8");
        echo $rss->getFeed();
    }

}

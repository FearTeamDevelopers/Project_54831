<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;

/**
 * Description of App_Controller_News
 *
 * @author Tomy
 */
class App_Controller_News extends Controller
{

    /**
     * Check if are sets category specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, App_Model_News $object)
    {
        if($object->getMetaTitle() != ''){
            $layoutView->set('metatitle', $object->getMetaTitle());
        }
        
        if($object->getMetaDescription() != ''){
            $layoutView->set('metadescription', $object->getMetaDescription());
        }
        
        if($object->getMetaImage() != ''){
            $layoutView->set('metaogimage', 'http://marko.in'.$object->getMetaImage());
        }
        
        $layoutView->set('metaogurl', 'http://marko.in/news/detail/'.$object->getUrlKey());
        $layoutView->set('metaogtype', 'article');
        
        return;
    }
    
    /**
     *
     * @param \App_Model_News $news
     */
    private function _parseNewsBody(\App_Model_News $news, $parsedField)
    {
        preg_match_all('/\(\!(video|photo|read)_[0-9a-z]+\!\)/', $news->$parsedField, $matches);
        $m = array_shift($matches);

        foreach ($m as $match) {
            $match = str_replace(array('(!', '!)'), '', $match);
            list($type, $id) = explode('_', $match);

            $body = $news->$parsedField;
            if ($type == 'photo') {
                $photo = App_Model_Photo::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('photoName', 'imgThumb', 'imgMain', 'description')
                );

                $tag = "<a href=\"{$photo->imgMain}\" class=\"highslide\" title=\"{$photo->description}\""
                        . " onclick=\"return hs.expand(this, confignews)\">"
                        . "<img src=\"{$photo->imgThumb}\" alt=\"Marko.in\"/></a>";

                $body = str_replace("(!photo_{$id}!)", $tag, $body);

                $news->$parsedField = $body;
            }

            if ($type == 'video') {
                $video = App_Model_Video::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('title', 'path', 'width', 'height')
                );

                $tag = "<iframe width=\"450\" height=\"253\" "
                        . "src=\"{$video->path}\" frameborder=\"0\" allowfullscreen></iframe>";

                $body = str_replace("(!video_{$id}!)", $tag, $body);
                $news->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"#\" class=\"ajaxLink news-read-more\" id=\"show_news-detail_{$news->getUrlKey()}\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $news->$parsedField = $body;
            }
        }
        
        $news->fbLikeUrl = urlencode('http://'.RequestMethods::server('HTTP_HOST').'/news/detail/'.$news->getUrlKey());

        return $news;
    }

    /**
     *
     * @param type $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $this->checkRefferer('shownews');
        $this->willRenderActionView = true;
        
        $npp = (int) $this->loadConfigFromDb('news_per_page');
        
        $news = App_Model_News::all(
                    array('active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s')), 
                    array('id', 'urlKey', 'author', 'title', 'shortBody', 'created', 'rank'), 
                    array('rank' => 'asc','created' => 'DESC'), $npp, (int) $page);

        if ($news !== null) {
            foreach ($news as $_news) {
                $this->_parseNewsBody($_news, 'shortBody');
            }
        } else {
            $news = array();
        }

        $view->set('newsbatch', $news);
    }

    /**
     *
     * @param type $title
     */
    public function detail($title)
    {
        $view = $this->getActionView();
        $this->checkRefferer('shownd');

        $news = App_Model_News::first(
                        array(
                    'urlKey = ?' => $title,
                    'active = ?' => true
                        ));

        $newsParsed = $this->_parseNewsBody($news, 'body');

        if($this->willRenderLayoutView){
            $layoutView = $this->getLayoutView();
            $this->_checkMetaData($layoutView, $news);
            $layoutView
                ->set('article', 1)
                ->set('artcreated', $news->getCreated())
                ->set('artmodified', $news->getModified());
        }
        
        $view->set('news', $newsParsed);
    }

}

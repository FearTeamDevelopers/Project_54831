<?php

use App\Etc\Controller;

/**
 * Description of IndexController
 *
 * @author Tomy
 */
class App_Controller_News extends Controller
{

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
                                ), array('photoName', 'thumbPath', 'path')
                );

                $tag = "<a href=\"{$photo->path}\" class=\"highslide\" title=\"{$photo->photoName}\""
                        . " onclick=\"return hs.expand(this, confignews)\">"
                        . "<img src=\"{$photo->thumbPath}\" alt=\"Marko.in\"/></a>";

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
            
            if ($type == 'read'){
                $tag = "<a href=\"#\" class=\"ajaxLink newsReadMore\" id=\"show_news-detail_{$news->getUrlKey()}\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $news->$parsedField = $body;
            }
        }

        return $news;
    }

    /**
     *
     * @param type $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        
        if($view->getHttpReferer() === null){
            $this->willRenderLayoutView = true;
            $this->willRenderLayoutView = true;
            $layoutView = $this->getLayoutView();
            $layoutView->set('hidetop', true)
                    ->set('shownews', true);
        }else{
            $this->willRenderLayoutView = false;
        }
        
        $this->willRenderActionView = true;

        $news = App_Model_News::all(
                    array('active = ?' => true), 
                    array('id', 'urlKey', 'author', 'title', 'shortBody', 'created'), 
                    array('created' => 'DESC'), 5, $page);

        if ($news !== null) {
            $newsCount = App_Model_News::count();
            $newsPageCount = ceil($newsCount / 5);

            if (!empty($page)) {
                $page = (int) $page;
            } else {
                $page = 1;
            }

            foreach ($news as $_news) {
                $this->_parseNewsBody($_news, 'shortBody');
            }
        }else{
            $news = array();
            $newsPageCount = 0;
        }

        $view->set('newsbatch', $news)
                ->set('newspagecount', $newsPageCount);
    }

    /**
     *
     * @param type $title
     */
    public function detail($title)
    {
        $view = $this->getActionView();
        
        if($view->getHttpReferer() === null){
            $this->willRenderLayoutView = true;
            $layoutView = $this->getLayoutView();
            $layoutView->set('hidetop', true)
                    ->set('shownd', true);
        }else{
            $this->willRenderLayoutView = false;
        }
        
        $this->willRenderActionView = true;

        $news = App_Model_News::first(
                        array(
                    'urlKey = ?' => $title,
                    'active = ?' => true
                        ), array('id', 'author', 'title', 'body', 'created'));

        $newsParsed = $this->_parseNewsBody($news, 'body');

        $view->set('news', $newsParsed);
    }

}

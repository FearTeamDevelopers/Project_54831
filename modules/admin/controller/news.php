<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_News extends Controller
{
    
    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_News::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @before _secured, _publisher
     */
    private function _getPhotos()
    {
        $photoQuery = App_Model_Photo::getQuery(
                        array('ph.id', 'ph.thumbPath', 'ph.path'))
                ->join('tb_photosection', 'phs.photoId = ph.id', 'phs', 
                        array('phs.photoId', 'phs.sectionId'))
                ->join('tb_section', 'phs.sectionId = s.id', 's', 
                        array('s.urlKey'))
                ->where('s.urlKey = ?', 'news')
                ->order('ph.created', 'DESC');

        $photos = App_Model_Photo::initialize($photoQuery);

        return $photos;
    }

    /**
     * 
     * @before _secured, _publisher
     */
    private function _getVideos()
    {
        $videoQuery = App_Model_Video::getQuery(
                        array('vi.id', 'vi.path'))
                ->join('tb_videosection', 'vis.videoId = vi.id', 'vis',
                        array('vis.videoId', 'vis.sectionId'))
                ->join('tb_section', 'vis.sectionId = s.id', 's',
                        array('s.urlKey'))
                ->where('s.urlKey = ?', 'news')
                ->order('vi.created', 'DESC');

        $videos = App_Model_Video::initialize($videoQuery);

        return $videos;
    }

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $news = App_Model_News::all();
        
        $view->set('news', $news);
    }

    /**
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();

        $photos = $this->_getPhotos();
        $videos = $this->_getVideos();

        $view->set('photos', $photos)
                ->set('videos', $videos)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());
        
        if (RequestMethods::post('submitAddNews')) {
            if($this->checkToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/news/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('urlkey'));
            
            if(!$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }

            $news = new App_Model_News(array(
                'title' => RequestMethods::post('title'),
                'author' => RequestMethods::post('author', $this->getUser()->getWholeName()),
                'urlKey' => $urlKey,
                'shortBody' => RequestMethods::post('shorttext'),
                'rssFeedBody' => RequestMethods::post('feedtext', ''),
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration'),
                'rank' => RequestMethods::post('rank', 1),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaDescription' => RequestMethods::post('metadescription', RequestMethods::post('shorttext')),
                'metaImage' => RequestMethods::post('metaimage', '')
            ));

            if (empty($errors) && $news->validate()) {
                $id = $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage('News'.self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $news->getErrors())
                        ->set('news', $news);
            }
        }
    }

    /**
     * @before _secured, _publisher
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $photos = $this->_getPhotos();
        $videos = $this->_getVideos();

        $news = App_Model_News::first(array('id = ?' => (int)$id));

        if ($news === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/news/');
        }
        
        $view->set('news', $news)
                ->set('photos', $photos)
                ->set('videos', $videos);

        if (RequestMethods::post('submitEditNews')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/news/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('urlkey'));

            if($news->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }
            
            $news->title = RequestMethods::post('title');
            $news->urlKey = $urlKey;
            $news->author = RequestMethods::post('author', $this->getUser()->getWholeName());
            $news->expirationDate = RequestMethods::post('expiration');
            $news->body = RequestMethods::post('text');
            $news->shortBody = RequestMethods::post('shorttext');
            $news->rssFeedBody = RequestMethods::post('feedtext', '');
            $news->rank = RequestMethods::post('rank', 1);
            $news->active = RequestMethods::post('active');
            $news->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $news->metaDescription = RequestMethods::post('metadescription', RequestMethods::post('shorttext'));
            $news->metaImage = RequestMethods::post('metaimage', '');

            if (empty($errors) && $news->validate()) {
                $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                $view->set('errors', $errors + $news->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        
        if ($this->checkToken()) {
            $news = App_Model_News::first(
                            array('id = ?' => (int) $id), array('id')
            );

            if (NULL === $news) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($news->delete()) {
                    Event::fire('admin.log', array('success', 'News id: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'News id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performNewsAction')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/news/');
            }
            
            $ids = RequestMethods::post('newsids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            if (!$_news->delete()) {
                                $errors[] = 'An error occured while deleting ' . $_news->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'News ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');

                    break;
                case 'activate':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            $_news->active = true;

                            if ($_news->validate()) {
                                $_news->save();
                            } else {
                                $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                        . join(', ', $_news->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'News ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');

                    break;
                case 'deactivate':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            $_news->active = false;

                            if ($_news->validate()) {
                                $_news->save();
                            } else {
                                $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                        . join(', ', $_news->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'News ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');
                    break;
                default:
                    self::redirect('/admin/news/');
                    break;
            }
        }
    }

}

<?php

use Admin\Etc\Controller as Controller;
use THCFrame\Request\RequestMethods as RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;

class Admin_Controller_News extends Controller
{
    
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
                ->where('s.urlKey = ?', 'news');

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
                ->where('s.urlKey = ?', 'news');

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

        if (RequestMethods::post('submitAddNews')) {
            $urlKey = StringMethods::removeDiacriticalMarks(RequestMethods::post('urlkey'));

            $news = new App_Model_News(array(
                'title' => RequestMethods::post('title'),
                'author' => RequestMethods::post('author'),
                'urlKey' => $urlKey,
                'shortBody' => RequestMethods::post('shorttext'),
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration')
            ));

            if ($news->validate()) {
                $id = $news->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('News has been successfully saved');
                self::redirect('/admin/news/');
            } else {
                $view->set('errors', $news->getErrors())
                        ->set('news', $news);
                Event::fire('admin.log', array('fail', 'ID:'));
            }
        }

        $view->set('photos', $photos)
                ->set('videos', $videos);
    }

    /**
     * @before _secured, _publisher
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $photos = $this->_getPhotos();
        $videos = $this->_getVideos();

        $news = App_Model_News::first(array('id = ?' => $id));

        if ($news === null) {
            $view->errorMessage('News not found');
            self::redirect('/admin/news/');
        }

        if (RequestMethods::post('submitEditNews')) {
            $urlKey = StringMethods::removeDiacriticalMarks(RequestMethods::post('urlkey'));

            $news->title = RequestMethods::post('title');
            $news->urlKey = $urlKey;
            $news->author = RequestMethods::post('author');
            $news->expirationDate = RequestMethods::post('expiration');
            $news->body = RequestMethods::post('text');
            $news->shortBody = RequestMethods::post('shorttext');
            $news->active = RequestMethods::post('active');

            if ($news->validate()) {
                $news->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/news/');
            } else {
                $view->set('errors', $news->getErrors());
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
            }
        }

        $view->set('news', $news)
                ->set('photos', $photos)
                ->set('videos', $videos);
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $news = App_Model_News::first(
                        array('id = ?' => $id), array('id')
        );

        if (NULL === $news) {
            echo 'News not found';
        } else {
            if ($news->delete()) {
                Event::fire('admin.log', array('success', 'ID: ' . $id));
                echo 'ok';
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                echo 'Unknown error eccured';
            }
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
            $ids = RequestMethods::post('newsids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($news as $_news) {
                        if (NULL !== $_news) {
                            if (!$_news->delete()) {
                                $errors[] = 'An error occured while deleting ' . $_news->getTitle();
                            }
                        } else {
                            $errors[] = "News with id {$_news->getId()} not found<br/>";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Videos have been deleted');
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

                    foreach ($news as $_news) {
                        if (NULL !== $_news) {
                            $_news->active = true;

                            if ($_news->validate()) {
                                $_news->save();
                            } else {
                                $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                        . join(', ', $_news->getErrors());
                            }
                        } else {
                            $errors[] = "News with id {$_news->getId()} not found";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Videos have been activated');
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

                    foreach ($news as $_news) {
                        if (NULL !== $_news) {
                            $_news->active = false;

                            if ($_news->validate()) {
                                $_news->save();
                            } else {
                                $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                        . join(', ', $_news->getErrors());
                            }
                        } else {
                            $errors[] = "News with id {$_news->getId()} not found";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Videos have been deactivated');
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

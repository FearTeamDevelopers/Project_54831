<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_Video extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $videos = App_Model_Video::all();

        foreach ($videos as $video) {
            $sectionString = '';
            $sectionArr = array();
            
            $videosQuery = App_Model_VideoSection::getQuery(array('vis.videoId', 'vis.sectionId'))
                    ->join('tb_section', 'vis.sectionId = s.id', 's', 
                            array('s.urlKey' => 'secUrlKey', 's.title' => 'secTitle'))
                    ->where('vis.videoId = ?', $video->id);

            $sections = App_Model_VideoSection::initialize($videosQuery);

            foreach ($sections as $section) {
                $sectionArr[] = ucfirst($section->secTitle);
            }
            $sectionString = join(', ', $sectionArr);

            $video->inSections = $sectionString;
        }

        $view->set('videos', $videos);
    }

    /**
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();
        $errors = array();

        $sections = App_Model_Section::all(
                        array(
                    'active = ?' => true,
                    'supportVideo = ?' => true
                        ), array('id', 'urlKey', 'title')
        );
        
        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddVideo')) {
            $this->checkToken();
            $path = str_replace('watch?v=', 'embed/', RequestMethods::post('path'));
            
            $video = new App_Model_Video(array(
                'title' => RequestMethods::post('title'),
                'path' => $path,
                'width' => RequestMethods::post('width', 500),
                'height' => RequestMethods::post('height', 281),
                'priority' => RequestMethods::post('priority', 0)
            ));

            $sectionsIds = (array) RequestMethods::post('sections');
            if (empty($sectionsIds[0])) {
                $errors['sections'] = array('At least one section has to be selected');
            }

            if (empty($errors) && $video->validate()) {
                $id = $video->save();

                foreach ($sectionsIds as $section) {
                    $videoSection = new App_Model_VideoSection(array(
                        'videoId' => $id,
                        'sectionId' => (int) $section,
                    ));
                    $videoSection->save();
                }

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Video has been successfully saved');
                self::redirect('/admin/video/');
            } else {
                $view->set('errors', $video->getErrors())
                        ->set('video', $video);
                Event::fire('admin.log', array('fail'));
            }
        }
    }

    /**
     * @before _secured, _publisher
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $errors = array();

        $sections = App_Model_Section::all(
                        array(
                    'active = ?' => true,
                    'supportVideo = ?' => true
                        ), array('id', 'urlKey', 'title')
        );

        $video = App_Model_Video::first(array('id = ?' => $id));

        if (NULL === $video) {
            $view->errorMessage('Video not found');
            self::redirect('/admin/video/');
        }

        $videoSectionQuery = App_Model_VideoSection::getQuery(array('vis.videoId', 'vis.sectionId'))
                ->join('tb_section', 'vis.sectionId = s.id', 's', 
                        array('s.urlKey' => 'secUrlKey', 's.title' => 'secTitle'))
                ->where('vis.videoId = ?', $video->id);
        $videoSections = App_Model_VideoSection::initialize($videoSectionQuery);

        foreach ($videoSections as $section) {
            $sectionArr[] = $section->secTitle;
        }
        
        $video->inSections = $sectionArr;
        $view->set('video', $video)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditVideo')) {
            $this->checkToken();
            $path = str_replace('watch?v=', 'embed/', RequestMethods::post('path'));
            
            $video->title = RequestMethods::post('title');
            $video->path = $path;
            $video->width = RequestMethods::post('width', 500);
            $video->height = RequestMethods::post('height', 281);
            $video->priority = RequestMethods::post('priority', 0);
            $video->active = RequestMethods::post('active');

            $sectionsIds = (array) RequestMethods::post('sections');

            if (empty($sectionsIds[0])) {
                $errors['sections'] = array('At least one section has to be selected');
            }

            if (empty($errors) && $video->validate()) {
                $video->save();

                $status = App_Model_VideoSection::deleteAll(array('videoId = ?' => $id));
                if ($status != -1) {
                    foreach ($sectionsIds as $sectionId) {
                        $videoSection = new App_Model_VideoSection(array(
                            'videoId' => $id,
                            'sectionId' => (int) $sectionId
                        ));

                        $videoSection->save();
                    }
                }

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/video/');
            } else {
                $view->set('errors', $video->getErrors());
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
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
        $this->checkToken();

        $video = App_Model_Video::first(
                        array('id = ?' => $id), array('id')
        );

        if (NULL === $video) {
            echo 'Video not found';
        } else {
            if ($video->delete()) {
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

        if (RequestMethods::post('performVideoAction')) {
            $this->checkToken();
            $ids = RequestMethods::post('videoids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $videos = App_Model_Video::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $videos) {
                        foreach ($videos as $video) {

                            if (!$video->delete()) {
                                $errors[] = 'An error occured while deleting ' . $video->getTitle();
                            }
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

                    self::redirect('/admin/video/');

                    break;
                case 'activate':
                    $videos = App_Model_Video::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $videos) {
                        foreach ($videos as $video) {
                            $video->active = true;

                            if ($video->validate()) {
                                $video->save();
                            } else {
                                $errors[] = "Video id {$video->getId()} - {$video->getTitle()} errors: "
                                        . join(', ', $video->getErrors());
                            }
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

                    self::redirect('/admin/video/');

                    break;
                case 'deactivate':
                    $videos = App_Model_Video::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $videos) {
                        foreach ($videos as $video) {
                            $video->active = false;

                            if ($video->validate()) {
                                $video->save();
                            } else {
                                $errors[] = "Video id {$video->getId()} - {$video->getTitle()} errors: "
                                        . join(', ', $video->getErrors());
                            }
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

                    self::redirect('/admin/video/');
                    break;
                default:
                    self::redirect('/admin/video/');
                    break;
            }
        }
    }

}

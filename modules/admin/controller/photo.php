<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Photo extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        
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
                    'supportPhoto = ?' => true
                        ), array('id', 'urlKey', 'title')
        );

        $view->set('sections', $sections)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddPhoto')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/photo/');
            }

            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            $fileErrors = $fileManager->uploadImage('photos', 'section_photos', time().'_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            $sectionsIds = (array) RequestMethods::post('sections');
            if (empty($sectionsIds[0])) {
                $errors['sections'] = array('At least one section has to be selected');
            }

            if (!empty($files) && empty($errors)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $info = $file->getOriginalInfo();

                        $photo = new App_Model_Photo(array(
                            'description' => RequestMethods::post('description'),
                            'category' => RequestMethods::post('category'),
                            'priority' => RequestMethods::post('priority', 0),
                            'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                            'imgMain' => trim($file->getFilename(), '.'),
                            'imgThumb' => trim($file->getThumbname(), '.'),
                            'mime' => $info['mime'],
                            'format' => $info['format'],
                            'width' => $file->getWidth(),
                            'height' => $file->getHeight(),
                            'size' => $file->getSize()
                        ));

                        if ($photo->validate()) {
                            $photoId = $photo->save();

                            foreach ($sectionsIds as $section) {
                                $photoSection = new App_Model_PhotoSection(array(
                                    'photoId' => $photoId,
                                    'sectionId' => (int) $section
                                ));
                                $photoSection->save();
                            }

                            Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                        } else {
                            Event::fire('admin.log', array('fail'));
                            $errors['photos'][] = $photo->getErrors();
                        }
                    }
                }
            }

            if (empty($errors) && empty($fileErrors)) {
                $view->successMessage(self::SUCCESS_MESSAGE_7);
                self::redirect('/admin/photo/');
            } else {
                $errors['photos'] = $fileErrors;
                $view->set('errors', $errors)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * @before _secured, _publisher
     * @param type $id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $errors = array();
        $collectionPhoto = false;

        $sections = App_Model_Section::all(
                        array(
                    'active = ?' => true,
                    'supportPhoto = ?' => true
                        ), array('id', 'urlKey', 'title')
        );

        $photo = App_Model_Photo::first(array('id = ?' => (int) $id));

        if (NULL === $photo) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/photo/');
        }

        $photoSectionQuery = App_Model_PhotoSection::getQuery(
                        array('phs.photoId', 'phs.sectionId'))
                ->join('tb_section', 'phs.sectionId = s.id', 's', 
                        array('s.urlKey' => 'secUrlKey', 's.title' => 'secTitle'))
                ->where('phs.photoId = ?', $photo->id);
        $photoSections = App_Model_PhotoSection::initialize($photoSectionQuery);

        if ($photoSections !== null) {
            foreach ($photoSections as $section) {
                $sectionArr[] = $section->secTitle;
            }

            $photo->inSections = $sectionArr;
        } else {
            $collectionPhoto = true;
        }

        $view->set('photo', $photo)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditPhoto')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/photo/');
            }

            $photo->description = RequestMethods::post('description');
            $photo->category = RequestMethods::post('category');
            $photo->priority = RequestMethods::post('priority', 0);
            $photo->active = RequestMethods::post('active');

            if ($collectionPhoto) {
                if ($photo->validate()) {
                    $photo->save();

                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    $view->successMessage(self::SUCCESS_MESSAGE_2);
                    self::redirect('/admin/photo/');
                } else {
                    Event::fire('admin.log', array('fail', 'Photo id: ' . $id));
                    $view->set('errors', $errors + $photo->getErrors());
                }
            } else {
                $sectionsIds = (array) RequestMethods::post('sections');

                if (empty($sectionsIds[0])) {
                    $errors['sections'] = array('At least one section has to be selected');
                }

                if (empty($errors) && $photo->validate()) {
                    $photo->save();

                    $status = App_Model_PhotoSection::deleteAll(array('photoId = ?' => $id));
                    if ($status != -1) {
                        foreach ($sectionsIds as $sectionId) {
                            $photoSection = new App_Model_PhotoSection(array(
                                'photoId' => $id,
                                'sectionId' => (int) $sectionId
                            ));

                            $photoSection->save();
                        }
                    }

                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    $view->successMessage(self::SUCCESS_MESSAGE_2);
                    self::redirect('/admin/photo/');
                } else {
                    Event::fire('admin.log', array('fail', 'Photo id: ' . $id));
                    $view->set('errors', $errors + $photo->getErrors());
                }
            }
        }
    }

    /**
     * @before _secured, _admin
     * @param type $id
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $photo = App_Model_Photo::first(
                    array('id = ?' => (int) $id), 
                    array('id', 'imgThumb', 'imgMain', 'photoName')
        );

        if (NULL === $photo) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/photo/');
        }

        $view->set('photo', $photo);

        if (RequestMethods::post('submitDeletePhoto')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/photo/');
            }

            $cache = Registry::get('cache');

            $imgMain = $photo->getUnlinkPath();
            $imgThumb = $photo->getUnlinkThumbPath();

            if ($photo->delete()) {
                @unlink($imgMain);
                @unlink($imgThumb);

                Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                $view->successMessage('Photo' . self::SUCCESS_MESSAGE_3);
                $cache->invalidate();
                self::redirect('/admin/photo/');
            } else {
                Event::fire('admin.log', array('fail', 'Photo id: ' . $id));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/admin/photo/');
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;
        $errors = array();

        if ($this->checkCSRFToken()) {
            $ids = RequestMethods::post('photoids');
            $action = RequestMethods::post('action');
            $cache = Registry::get('cache');

            if (empty($ids)) {
                echo 'No row selected';
                return;
            }

            switch ($action) {
                case 'delete':
                    $photos = App_Model_Photo::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $photos) {
                        foreach ($photos as $photo) {

                            if (unlink($photo->getUnlinkPath()) && unlink($photo->getUnlinkThumbPath())) {
                                if (!$photo->delete()) {
                                    $errors[] = 'An error occured while deleting ' . $photo->getPhotoName();
                                }
                            } else {
                                $errors[] = 'An error occured while deleting files of ' . $photo->getPhotoName();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Photo ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_6;
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                case 'activate':
                    $photos = App_Model_Photo::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $photos) {
                        foreach ($photos as $photo) {
                            $photo->active = true;

                            if ($photo->validate()) {
                                $photo->save();
                            } else {
                                $errors[] = "Photo id {$photo->getId()} - "
                                        . "{$photo->getPhotoName()} errors: "
                                        . join(', ', array_shift($photo->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Photo ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_4;
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                case 'deactivate':
                    $photos = App_Model_Photo::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $photos) {
                        foreach ($photos as $photo) {
                            $photo->active = false;

                            if ($photo->validate()) {
                                $photo->save();
                            } else {
                                $errors[] = "Photo id {$photo->getId()} - "
                                        . "{$photo->getPhotoName()} errors: "
                                        . join(', ', array_shift($photo->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Photo ids: ' . join(',', $ids)));
                        $cache->invalidate();
                        echo self::SUCCESS_MESSAGE_5;
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        echo $message;
                    }

                    break;
                default:
                    echo self::ERROR_MESSAGE_1;
                    break;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * Ajax
     * 
     * @before _secured, _publisher
     */
    public function load()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $page = RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "ph.width='?' OR ph.height='?' "
                    . "OR ph.size='?' OR ph.photoName LIKE '%%?%%'";

            $photoQuery = App_Model_Photo::getQuery(
                            array('ph.id', 'ph.active', 'ph.photoName', 'ph.imgThumb', 'ph.imgMain',
                                'ph.size', 'ph.width', 'ph.height', 'ph.priority', 'ph.created'))
                    ->leftjoin('tb_photosection', 'ph.id = phs.photoId', 'phs', 
                            array('photoId', 'sectionId'))
                    ->leftjoin('tb_section', 'phs.sectionId = se.id', 'se', 
                            array('se.title' => 'secTitle'))
                    ->wheresql($whereCond, $search, $search, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $photoQuery->order('ph.id', $dir);
                } elseif ($column == 2) {
                    $photoQuery->order('ph.photoName', $dir);
                } elseif ($column == 3) {
                    $photoQuery->order('phs.sectionId', $dir);
                } elseif ($column == 4) {
                    $photoQuery->order('ph.size', $dir);
                } elseif ($column == 5) {
                    $photoQuery->order('ph.width', $dir);
                } elseif ($column == 7) {
                    $photoQuery->order('ph.priority', $dir);
                } elseif ($column == 8) {
                    $photoQuery->order('ph.created', $dir);
                }
            } else {
                $photoQuery->order('ph.id', 'DESC');
            }

            $limit = (int) RequestMethods::post('iDisplayLength', 50);
            $photoQuery->limit($limit, $page + 1);
            $photos = App_Model_Photo::initialize($photoQuery);

            $photoCountQuery = App_Model_Photo::getQuery(array('ph.id'))
                    ->leftjoin('tb_photosection', 'ph.id = phs.photoId', 'phs', 
                            array('photoId', 'sectionId'))
                    ->leftjoin('tb_section', 'phs.sectionId = se.id', 'se', 
                            array('se.title' => 'secTitle'))
                    ->wheresql($whereCond, $search, $search, $search, $search, $search);

            $photosCount = App_Model_Photo::initialize($photoCountQuery);
            unset($photoCountQuery);

            $count = count($photosCount);
            unset($photosCount);
        } else {
            $photoQuery = App_Model_Photo::getQuery(
                            array('ph.id', 'ph.active', 'ph.photoName', 'ph.imgThumb', 'ph.imgMain',
                                'ph.size', 'ph.width', 'ph.height', 'ph.priority', 'ph.created'))
                    ->leftjoin('tb_photosection', 'ph.id = phs.photoId', 'phs', 
                            array('photoId', 'sectionId'))
                    ->leftjoin('tb_section', 'phs.sectionId = se.id', 'se', 
                            array('se.title' => 'secTitle'));

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $photoQuery->order('ph.id', $dir);
                } elseif ($column == 2) {
                    $photoQuery->order('ph.photoName', $dir);
                } elseif ($column == 3) {
                    $photoQuery->order('phs.sectionId', $dir);
                } elseif ($column == 4) {
                    $photoQuery->order('ph.size', $dir);
                } elseif ($column == 5) {
                    $photoQuery->order('ph.width', $dir);
                } elseif ($column == 7) {
                    $photoQuery->order('ph.priority', $dir);
                } elseif ($column == 8) {
                    $photoQuery->order('ph.created', $dir);
                }
            } else {
                $photoQuery->order('ph.id', 'DESC');
            }

            $limit = (int) RequestMethods::post('iDisplayLength', 50);
            $photoQuery->limit($limit, $page + 1);
            $photos = App_Model_Photo::initialize($photoQuery);
            $photoCountQuery = App_Model_Photo::getQuery(array('ph.id'))
                    ->leftjoin('tb_photosection', 'ph.id = phs.photoId', 'phs', 
                            array('photoId', 'sectionId'))
                    ->leftjoin('tb_section', 'phs.sectionId = se.id', 'se', 
                            array('se.title' => 'secTitle'));

            $photosCount = App_Model_Photo::initialize($photoCountQuery);
            unset($photoCountQuery);
            $count = count($photosCount);
            unset($photosCount);
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $prodArr = array();
        if ($photos !== null) {
            foreach ($photos as $photo) {
                if ($photo->active) {
                    $label = "<span class='labelProduct labelProductGreen'>Active</span>";
                } else {
                    $label = "<span class='labelProduct labelProductGray'>Inactive</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $photo->id . "\"";
                $arr [] = "\"<img alt='' src='" . $photo->imgThumb . "' height='80px'/>\"";
                $arr [] = "\"" . $photo->photoName . "\"";
                $arr [] = "\"" . $photo->secTitle . "\"";
                $arr [] = "\"" . $photo->getFormatedSize() . "\"";
                $arr [] = "\"" . $photo->width . "x" . $photo->height . "\"";
                $arr [] = "\"" . $label . "\"";
                $arr [] = "\"" . (int) $photo->priority . "\"";
                $arr [] = "\"" . $photo->created . "\"";

                $tempStr = "<a href='/admin/photo/edit/" . $photo->id . "' class='btn btn3 btn_pencil' title='Edit'></a>";

                if ($this->isAdmin()) {
                    $tempStr .= "<a href='/admin/photo/delete/" . $photo->id . "' class='btn btn3 btn_trash' title='Delete'></a>";
                }
                $arr [] = "\"" . $tempStr . "\"]";
                $prodArr[] = join(',', $arr);
            }

            $str .= join(',', $prodArr) . "]}";
            echo $str;
        } else {
            $str .= "[ \"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]]}";

            echo $str;
        }
    }

}

<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\ArrayMethods;
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
        
        $fileManager = new FileManager(array(
            'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
            'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
            'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
            'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
            'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
        ));

        if (RequestMethods::post('submitAddPhoto')) {
            $this->checkToken();
            $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken'));
            $errors = array();
            
            try {
                $photoArr = $fileManager->upload('photo', 'section_photos');
                $uploaded = ArrayMethods::toObject($photoArr);
            } catch (Exception $ex) {
                $errors['photo'] = $ex->getMessage();
            }

            $photo = new App_Model_Photo(array(
                'description' => RequestMethods::post('description', ''),
                'category' => RequestMethods::post('category', ''),
                'priority' => RequestMethods::post('priority', 0),
                'photoName' => $uploaded->file->filename,
                'thumbPath' => trim($uploaded->thumb->path, '.'),
                'path' => trim($uploaded->file->path, '.'),
                'mime' => $uploaded->file->ext,
                'size' => $uploaded->file->size,
                'width' => $uploaded->file->width,
                'height' => $uploaded->file->height,
                'thumbSize' => $uploaded->thumb->size,
                'thumbWidth' => $uploaded->thumb->width,
                'thumbHeight' => $uploaded->thumb->height
            ));

            $sectionsIds = (array) RequestMethods::post('sections');
            if (empty($sectionsIds[0])) {
                $errors['sections'] = array('At least one section has to be selected');
            }
            
            if (empty($errors) && $photo->validate()) {
                $photoId = $photo->save();

                foreach ($sectionsIds as $section) {
                    $photoSection = new App_Model_PhotoSection(array(
                        'photoId' => $photoId,
                        'sectionId' => (int) $section
                    ));
                    $photoSection->save();
                }

                Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                $view->successMessage('Photo has been successfully uploaded');
                self::redirect('/admin/photo/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $photo->getErrors())
                        ->set('photo', $photo);
            }
        } elseif (RequestMethods::post('submitAddMultiPhoto')) {
            $this->checkToken();
            $errors = $errors['photos'] = array();
            
            try {
                $result = $fileManager->upload('photos', 'section_photos');
            } catch (Exception $ex) {
                $errors['photos'] = $ex->getMessage();
            }

            if (is_array($result) && !empty($result['errors'])) {
                $errors['photos'] = $result['errors'];
            }

            if (is_array($result) && !empty($result['files'])) {
                foreach ($result['files'] as $image) {
                    $object = ArrayMethods::toObject($image);

                    $photo = new App_Model_Photo(array(
                        'description' => RequestMethods::post('description', ''),
                        'category' => RequestMethods::post('category', ''),
                        'priority' => RequestMethods::post('priority', 0),
                        'photoName' => $object->file->filename,
                        'thumbPath' => trim($object->thumb->path, '.'),
                        'path' => trim($object->file->path, '.'),
                        'mime' => $object->file->ext,
                        'size' => $object->file->size,
                        'width' => $object->file->width,
                        'height' => $object->file->height,
                        'thumbSize' => $object->thumb->size,
                        'thumbWidth' => $object->thumb->width,
                        'thumbHeight' => $object->thumb->height
                    ));

                    $sectionsIds = (array) RequestMethods::post('sections');
                    if (empty($sectionsIds[0])) {
                        $errors['sections'] = array('At least one section has to be selected');
                    }

                    if (empty($errors) && $photo->validate()) {
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
                        $errors = $errors + $photo->getErrors();
                    }
                }

                if (empty($errors)) {
                    $view->successMessage('Photos have been successfully uploaded');
                    self::redirect('/admin/photo/');
                } else {
                    $view->set('errors', $errors);
                }
            }

            $view->set('errors', $errors);
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
            $view->errorMessage('Photo not found');
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
            $this->checkToken();

            $photo->description = RequestMethods::post('description', '');
            $photo->category = RequestMethods::post('category', '');
            $photo->priority = RequestMethods::post('priority', 0);
            $photo->active = RequestMethods::post('active');

            if ($collectionPhoto) {
                if ($photo->validate()) {
                    $photo->save();

                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    $view->successMessage('All changes were successfully saved');
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
                    $view->successMessage('All changes were successfully saved');
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
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $photo = App_Model_Photo::first(
                        array('id = ?' => (int) $id), 
                        array('id', 'thumbPath', 'path')
        );

        if (NULL === $photo) {
            echo 'Photo not found';
        } else {
            if (unlink($photo->getUnlinkPath()) && unlink($photo->getUnlinkThumbPath()) && $photo->delete()) {
                Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                echo 'ok';
            } else {
                Event::fire('admin.log', array('fail', 'Photo id: ' . $id));
                echo 'Unknown error eccured';
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

        $this->checkToken();
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
                    echo 'Photos have been deleted';
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
                    echo 'Photos have been activated';
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
                    echo 'Photos have been deactivated';
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                    $message = join('<br/>', $errors);
                    echo $message;
                }

                break;
            default:
                echo 'Unknown action';
                break;
        }
    }

    /**
     * Ajax
     * 
     * @before _secured, _publisher
     */
    public function checkPhoto()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $filename = pathinfo(RequestMethods::post('filename'), PATHINFO_FILENAME);

        $photo = App_Model_Photo::first(array(
                    'photoName LIKE ?' => $filename
        ));

        if ($photo === null) {
            echo 'ok';
        } else {
            echo "Photo with this name {$filename} already exits. Do you want to overwrite it?";
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
                    . "OR ph.size='?' OR se.title='?' "
                    . "OR ph.photoName LIKE '%%?%%'";

            $photoQuery = App_Model_Photo::getQuery(
                            array('ph.id', 'ph.active', 'ph.photoName', 'ph.thumbPath', 'ph.path',
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
                            array('ph.id', 'ph.active', 'ph.photoName', 'ph.thumbPath', 'ph.path',
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
            $count = App_Model_Photo::count();
        }
        
        $draw = $page + 1 + time();
        
        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $prodArr = array();
        if ($photos !== null) {
            foreach ($photos as $photo) {
                if($photo->active){
                    $label = "<span class='labelProduct labelProductGreen'>Active</span>";
                }else{
                    $label = "<span class='labelProduct labelProductGray'>Inactive</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $photo->id . "\"";
                $arr [] = "\"<img alt='' src='" . $photo->thumbPath . "' height='80px'/>\"";
                $arr [] = "\"" . $photo->photoName . "\"";
                $arr [] = "\"" . $photo->secTitle . "\"";
                $arr [] = "\"" . $photo->size . "\"";
                $arr [] = "\"" . $photo->width."x". $photo->height."\"";
                $arr [] = "\"" .$label."\"";
                $arr [] = "\"" . (int)$photo->priority . "\"";
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

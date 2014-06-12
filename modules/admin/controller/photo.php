<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\ImageManager;
use THCFrame\Core\ArrayMethods;

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
        $view = $this->getActionView();

        $photos = App_Model_Photo::all();

        foreach ($photos as $photo) {
            $sectionString = '';
            $sectionArr = array();

            $photoSectionQuery = App_Model_PhotoSection::getQuery(array('phs.photoId', 'phs.sectionId'))
                    ->join('tb_section', 'phs.sectionId = s.id', 's', 
                            array('s.urlKey' => 'secUrlKey', 's.title' => 'secTitle'))
                    ->where('phs.photoId = ?', $photo->id);

            $sections = App_Model_PhotoSection::initialize($photoSectionQuery);

            if ($sections !== null) {
                foreach ($sections as $section) {
                    $sectionArr[] = ucfirst($section->secTitle);
                }
                $sectionString = join(', ', $sectionArr);
                $photo->inSections = $sectionString;
            }
        }

        $view->set('photos', $photos);
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

        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddPhoto')) {
            $this->checkToken();
            $errors = array();
            
            try {
                $uploadTo = 'section_photos';
                $im = new ImageManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                    'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                    'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                ));
                
                $photoArr = $im->upload('photo', $uploadTo);
                $uploaded = ArrayMethods::toObject($photoArr);
            } catch (Exception $ex) {
                $errors['photo'] = $ex->getMessage();
            }

            $photo = new App_Model_Photo(array(
                'description' => RequestMethods::post('description', ''),
                'category' => RequestMethods::post('category', ''),
                'priority' => RequestMethods::post('priority', 0),
                'photoName' => $uploaded->photo->name,
                'thumbPath' => trim($uploaded->thumb->filename, '.'),
                'path' => trim($uploaded->photo->filename, '.'),
                'mime' => $uploaded->photo->mime,
                'size' => $uploaded->photo->size,
                'width' => $uploaded->photo->width,
                'height' => $uploaded->photo->height,
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

                Event::fire('admin.log', array('success', 'ID: ' . $photoId));
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
                $uploadTo = 'section_photos';
                $im = new ImageManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                    'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                    'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                ));
                
                $result = $im->upload('photos', $uploadTo);
            } catch (Exception $ex) {
                $errors['photos'] = $ex->getMessage();
            }

            if (is_array($result) && !empty($result['errors'])) {
                $errors['photos'] = $result['errors'];
            }

            if (is_array($result) && !empty($result['photos'])) {
                foreach ($result['photos'] as $image) {
                    $object = ArrayMethods::toObject($image);

                    $photo = new App_Model_Photo(array(
                        'description' => RequestMethods::post('description', ''),
                        'category' => RequestMethods::post('category', ''),
                        'priority' => RequestMethods::post('priority', 0),
                        'photoName' => $object->photo->name,
                        'thumbPath' => trim($object->thumb->filename, '.'),
                        'path' => trim($object->photo->filename, '.'),
                        'mime' => $object->photo->mime,
                        'size' => $object->photo->size,
                        'width' => $object->photo->width,
                        'height' => $object->photo->height,
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

                        Event::fire('admin.log', array('success', 'ID: ' . $photoId));
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

        $sections = App_Model_Section::all(
                        array(
                    'active = ?' => true,
                    'supportPhoto = ?' => true
                        ), array('id', 'urlKey', 'title')
        );

        $photo = App_Model_Photo::first(array('id = ?' => $id));

        if (NULL === $photo) {
            $view->errorMessage('Photo not found');
            self::redirect('/admin/photo/');
        }

        $photoSectionQuery = App_Model_PhotoSection::getQuery(array('phs.photoId', 'phs.sectionId'))
                ->join('tb_section', 'phs.sectionId = s.id', 's', 
                        array('s.urlKey' => 'secUrlKey', 's.title' => 'secTitle'))
                ->where('phs.photoId = ?', $photo->id);
        $photoSections = App_Model_PhotoSection::initialize($photoSectionQuery);

        foreach ($photoSections as $section) {
            $sectionArr[] = $section->secTitle;
        }

        $photo->inSections = $sectionArr;
        
        $view->set('photo', $photo)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditPhoto')) {
            $this->checkToken();
            
            $photo->description = RequestMethods::post('description', '');
            $photo->category = RequestMethods::post('category', '');
            $photo->priority = RequestMethods::post('priority', 0);
            $photo->active = RequestMethods::post('active');

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

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/photo/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $errors + $photo->getErrors());
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

        if ($this->checkTokenAjax()) {
            $photo = App_Model_Photo::first(
                            array('id = ?' => $id), array('id', 'thumbPath', 'path')
            );

            if (NULL === $photo) {
                echo 'Photo not found';
            } else {
                if ($photo->delete()) {
                    unlink($photo->getUnlinkPath());
                    unlink($photo->getUnlinkThumbPath());
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo 'Unknown error eccured';
                }
            }
        } else {
            echo 'Security token is not valid';
        }
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performPhotoAction')) {
            $this->checkToken();
            $ids = RequestMethods::post('photoids');
            $action = RequestMethods::post('action');

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
                        Event::fire('admin.log', array('delete success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Photos have been deleted');
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/photo/');

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
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Photos have been activated');
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/photo/');

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
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Photos have been deactivated');
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/photo/');
                    break;
                default:
                    self::redirect('/admin/photo/');
                    break;
            }
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

}

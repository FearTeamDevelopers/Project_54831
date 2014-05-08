<?php

use Admin\Etc\Controller as Controller;
use THCFrame\Filesystem\ImageManager as Image;
use THCFrame\Request\RequestMethods as RequestMethods;
use THCFrame\Events\Events as Event;

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
        Event::fire('admin.log');
        $view = $this->getActionView();

        $photos = App_Model_Photo::all();

        foreach ($photos as $photo) {
            $photoSectionQuery = App_Model_PhotoSection::getQuery(array('phs.photoId', 'phs.sectionId'))
                    ->join('tb_section', 'phs.sectionId = s.id', 's', 
                            array('s.urlKey' => 'secUrlKey', 's.title' => 'secTitle'))
                    ->where('phs.photoId = ?', $photo->id);

            $sections = App_Model_PhotoSection::initialize($photoSectionQuery);

            if (is_array($sections)) {
                foreach ($sections as $section) {
                    $sectionArr[] = ucfirst($section->secTitle);
                }
                $sectionString = join(', ', $sectionArr);
            } else {
                $sectionString = ucfirst($sections->secTitle);
            }

            $photo->inSections = $sectionString;
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
            $errors = array();
            try {
                $uploadTo = 'section_photos';
                $image = new Image;
                $image->upload('photo', $uploadTo)
                        ->resizeToHeight(180)
                        ->save(true);
            } catch (Exception $ex) {
                $errors['photo'] = $ex->getMessage();
            }

            $photo = new App_Model_Photo(array(
                'photoName' => $image->getFileName(),
                'thumbPath' => $image->getThumbPath(false),
                'path' => $image->getPath(false),
                'mime' => $image->getImageType(),
                'size' => $image->getSize(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'priority' => RequestMethods::post('priority'),
                'description' => RequestMethods::post('description', ''),
                'category' => RequestMethods::post('category', '')
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

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Photo has been successfully uploaded');
                self::redirect('/admin/photo/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $photo->getErrors())
                        ->set('photo', $photo);
            }
        } elseif (RequestMethods::post('submitAddMultiPhoto')) {
            $errors = $errors['photom'] = array();
            try {
                $uploadTo = 'section_photos';
                $image = new Image;
                $result = $image->upload('photos', $uploadTo);
            } catch (Exception $ex) {
                $errors['photom'] = $ex->getMessage();
            }

            if (is_array($result) && !empty($result['errors'])) {
                $errors['photom'] = $result['errors'];
            }

            if (is_array($result) && !empty($result['photos'])) {
                foreach ($result['photos'] as $image) {
                    $image->resizeToHeight(180)->save(true);

                    $photo = new App_Model_Photo(array(
                        'photoName' => $image->getFileName(),
                        'thumbPath' => $image->getThumbPath(false),
                        'path' => $image->getPath(false),
                        'mime' => $image->getImageType(),
                        'size' => $image->getSize(),
                        'width' => $image->getWidth(),
                        'height' => $image->getHeight(),
                        'priority' => RequestMethods::post('priority'),
                        'description' => RequestMethods::post('description', ''),
                        'category' => RequestMethods::post('category', '')
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

        if (is_array($photoSections)) {
            foreach ($photoSections as $section) {
                $sectionArr[] = $section->secTitle;
            }
        } else {
            $sectionArr[] = $photoSections->secTitle;
        }
        $photo->inSections = $sectionArr;

        if (RequestMethods::post('submitEditPhoto')) {
            $photo->description = RequestMethods::post('description');
            $photo->category = RequestMethods::post('category');
            $photo->priority = RequestMethods::post('priority');
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

        $view->set('photo', $photo)
                ->set('sections', $sections);
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
                        array('id = ?' => $id), array('id', 'thumbPath', 'path')
        );

        if (NULL === $photo) {
            echo 'Photo not found';
        } else {
            if ($photo->delete() 
                    && unlink($photo->getUnlinkPath()) && unlink($photo->getUnlinkThumbPath())) {
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

        if (RequestMethods::post('performPhotoAction')) {
            $ids = RequestMethods::post('photoids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $photos = App_Model_Photo::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($photos as $photo) {
                        if (NULL !== $photo) {
                            if (unlink($photo->getUnlinkPath()) && unlink($photo->getUnlinkThumbPath())) {
                                if (!$photo->delete()) {
                                    $errors[] = 'An error occured while deleting ' . $photo->getPhotoName();
                                }
                            } else {
                                $errors[] = 'An error occured while deleting files of ' . $photo->getPhotoName();
                            }
                        } else {
                            $errors[] = "Photo with id {$photo->getId()} not found<br/>";
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

                    foreach ($photos as $photo) {
                        if (NULL !== $photo) {
                            $photo->active = true;

                            if ($photo->validate()) {
                                $photo->save();
                            } else {
                                $errors[] = "Photo id {$photo->getId()} - "
                                        . "{$photo->getPhotoName()} errors: " 
                                        . join(', ', array_shift($photo->getErrors()));
                            }
                        } else {
                            $errors[] = "Photo with id {$photo->getId()} not found";
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

                    foreach ($photos as $photo) {
                        if (NULL !== $photo) {
                            $photo->active = false;

                            if ($photo->validate()) {
                                $photo->save();
                            } else {
                                $errors[] = "Photo id {$photo->getId()} - "
                                        . "{$photo->getPhotoName()} errors: " 
                                        . join(', ', array_shift($photo->getErrors()));
                            }
                        } else {
                            $errors[] = "Photo with id {$photo->getId()} not found";
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
            echo "Photo with this name {$filename} already exits. Do you want to rewrite it?";
        }
    }

}

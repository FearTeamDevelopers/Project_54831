<?php

use Admin\Etc\Controller as Controller;
use THCFrame\Filesystem\ImageManager as Image;
use THCFrame\Request\RequestMethods as RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_Collection extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        Event::fire('admin.log');
        $view = $this->getActionView();

        $collectionQuery = App_Model_Collection::getQuery(
                        array('cl.id', 'cl.active', 'cl.title', 'cl.created', 'cl.photographer', 'cl.season'))
                ->join('tb_collectionmenu', 'cl.menuId = clm.id', 'clm', 
                        array('clm.id' => 'menuId', 'clm.title' => 'menuTitle', 'clm.urlKey' => 'menuUrlKey'))
                ->join('tb_section', 'clm.sectionId = s.id', 's', 
                        array('s.id' => 'secId', 's.title' => 'secTitle'));

        $collections = App_Model_Collection::initialize($collectionQuery);

        $view->set('collections', $collections);
    }

    /**
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();
        $menu = App_Model_CollectionMenu::all(
                        array('active = ?' => true), 
                        array('id', 'title')
        );

        if (RequestMethods::post('submitAddCollection')) {
            $collection = new App_Model_Collection(array(
                'menuId' => RequestMethods::post('show'),
                'title' => RequestMethods::post('title'),
                'year' => RequestMethods::post('year'),
                'season' => RequestMethods::post('season'),
                'date' => RequestMethods::post('date'),
                'photographer' => RequestMethods::post('photographer'),
                'description' => RequestMethods::post('description')
            ));

            if ($collection->validate()) {
                $id = $collection->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Collection has been successfully saved');
                self::redirect('/admin/collection/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('collection', $collection)
                        ->set('errors', $collection->getErrors());
            }
        }

        $view->set('menu', $menu);
    }

    /**
     * 
     * @before _secured, _publisher
     * @param int $id   collection id
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $collectionQuery = App_Model_Collection::getQuery(array('cl.*'))
                ->join('tb_collectionmenu', 'cl.menuId = m.id', 'm', 
                        array('m.title' => 'menuTitle'))
                ->join('tb_section', 'm.sectionId = s.id', 's', 
                        array('s.id' => 'sectId', 's.title' => 'sectionTitle'))
                ->where('cl.id = ?', $id);

        $collection = App_Model_Collection::initialize($collectionQuery);

        if (!empty($collection)) {
            $collectionPhotoCount = App_Model_CollectionPhoto::count(array('collectionId = ?' => $id));

            $query = App_Model_Photo::getQuery(array('ph.*'))
                    ->join('tb_collectionphoto', 'clp.photoId = ph.id', 'clp', 
                            array('clp.collectionId'))
                    ->where('clp.collectionId = ?', $id);
            $collectionPhotos = App_Model_Photo::initialize($query);

            $view->set('collection', $collection)
                    ->set('collectionphotocount', $collectionPhotoCount)
                    ->set('photos', $collectionPhotos);
        } else {
            $view->warningMessage('Collection not foud');
            self::redirect('/admin/collection/');
        }
    }

    /**
     * 
     * @before _secured, _publisher
     * @param int $id   collection id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $collection = App_Model_Collection::first(array(
                    'id = ?' => $id
        ));

        if (NULL === $collection) {
            $view->errorMessage('Collection not found');
            self::redirect('/admin/collection/');
        }

        $menu = App_Model_CollectionMenu::all(
                        array('active = ?' => true), 
                        array('id', 'title')
        );

        if (RequestMethods::post('submitEditCollection')) {
            $collection->title = RequestMethods::post('title');
            $collection->menuId = RequestMethods::post('show');
            $collection->active = RequestMethods::post('active');
            $collection->year = RequestMethods::post('year');
            $collection->season = RequestMethods::post('season');
            $collection->date = RequestMethods::post('date');
            $collection->photographer = RequestMethods::post('photographer');
            $collection->description = RequestMethods::post('description');

            if ($collection->validate()) {
                $collection->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/collection/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $collection->getErrors());
            }
        }

        $view->set('collection', $collection)
                ->set('menu', $menu);
    }

    /**
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $collection = App_Model_Collection::first(
                        array('id = ?' => $id), 
                        array('id', 'title', 'description', 'created')
        );

        if (NULL === $collection) {
            $view->errorMessage('Collection not found');
            self::redirect('/admin/collection/');
        }

        $view->set('collection', $collection);

        if (RequestMethods::post('submitDeleteCollection')) {
            if (NULL !== $collection) {
                if ($collection->delete()) {
                    if (RequestMethods::post('action') == 1) {
                        rmdir('./public/uploads/images/collections/' . $collection->getId());
                    }

                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    $view->successMessage('Collection has been deleted');
                    self::redirect('/admin/collection/');
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    $view->errorMessage('Unknown error eccured');
                    self::redirect('/admin/collection/');
                }
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->errorMessage('Unknown id provided');
                self::redirect('/admin/collection/');
            }
        }
    }

    /**
     * 
     * @before _secured, _publisher
     * @param int $id   collection id
     */
    public function addPhoto($id)
    {
        $view = $this->getActionView();

        $collection = App_Model_Collection::first(
                        array(
                    'id = ?' => $id,
                    'active = ?' => true
                        ), array('id', 'title')
        );

        $view->set('collection', $collection);

        if (RequestMethods::post('submitAddPhoto')) {
            $errors = array();
            try {
                $uploadTo = 'collections/' . $id;
                $image = new Image;
                $image->upload('photo', $uploadTo)
                        ->resizeToHeight(180)
                        ->save(true);
            } catch (Exception $ex) {
                $errors['photo'] = $ex->getMessage();
            }

            $photo = new App_Model_Photo(array(
                'description' => RequestMethods::post('description'),
                'category' => RequestMethods::post('category'),
                'priority' => RequestMethods::post('priority'),
                'photoName' => $image->getFileName(),
                'thumbPath' => $image->getThumbPath(false),
                'path' => $image->getPath(false),
                'mime' => $image->getImageType(),
                'size' => $image->getSize(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight()
            ));

            if (empty($errors) && $photo->validate()) {
                $photoId = $photo->save();

                $collectionPhoto = new App_Model_CollectionPhoto(array(
                    'photoId' => $photoId,
                    'collectionId' => $collection->getId(),
                ));

                $collectionPhoto->save();

                Event::fire('admin.log', array('success', 'ID: ' . $photoId));
                $view->successMessage('Photo has been successfully uploaded');
                self::redirect('/admin/collection/detail/' . $id);
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $photo->getErrors());
            }
        } elseif (RequestMethods::post('submitAddMultiPhoto')) {
            $errors = array();
            try {
                $uploadTo = 'collections/' . $id;
                $image = new Image;
                $result = $image->upload('photos', $uploadTo);
            } catch (Exception $ex) {
                $errors['photo'] = $ex->getMessage();
            }

            if (is_array($result) && !empty($result['errors'])) {
                $errors['photo'] = $result['errors'];
            }

            if (is_array($result) && !empty($result['photos'])) {
                foreach ($result['photos'] as $image) {
                    $image->resizeToHeight(180)->save(true);

                    $photo = new App_Model_Photo(array(
                        'description' => RequestMethods::post('description', ''),
                        'category' => RequestMethods::post('category', ''),
                        'priority' => RequestMethods::post('priority', 0),
                        'photoName' => $image->getFileName(),
                        'thumbPath' => $image->getThumbPath(false),
                        'path' => $image->getPath(false),
                        'mime' => $image->getImageType(),
                        'size' => $image->getSize(),
                        'width' => $image->getWidth(),
                        'height' => $image->getHeight()
                    ));

                    if (empty($errors) && $photo->validate()) {
                        $photoId = $photo->save();

                        $collectionPhoto = new App_Model_CollectionPhoto(array(
                            'photoId' => $photoId,
                            'collectionId' => $collection->getId()
                        ));

                        $collectionPhoto->save();

                        Event::fire('admin.log', array('success', 'ID: ' . $photoId));
                    } else {
                        Event::fire('admin.log', array('fail'));
                        $errors += $photo->getErrors();
                    }
                }

                if (empty($errors)) {
                    $view->successMessage('Photos have been successfully uploaded');
                    self::redirect('/admin/collection/detail/' . $id);
                } else {
                    $view->set('errors', $errors);
                }
            }
        }
    }

    /**
     * Ajax
     * 
     * @before _secured, _publisher
     * @param int $id   photo id
     */
    public function deletePhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $photo = App_Model_Photo::first(
                        array('id = ?' => $id), array('id', 'path', 'thumbPath')
        );

        if (null === $photo) {
            echo 'Photo not found';
        } else {
            if ($photo->delete()) {
                if (unlink($photo->getUnlinkPath()) && unlink($photo->getUnlinkThumbPath())) {
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo 'Unknown error eccured while deleting images';
                }
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                echo 'Unknown error eccured';
            }
        }
    }

    /**
     * Ajax
     * 
     * @before _secured, _publisher
     * @param int $id   photo id
     */
    public function changePhotoStatus($id)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        $photo = App_Model_Photo::first(
                        array('id = ?' => $id)
        );

        if (null === $photo) {
            echo 'Photo not found';
        } else {
            if (!$photo->active) {
                $photo->active = true;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'active';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            } elseif ($photo->active) {
                $photo->active = false;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'inactive';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            }
        }
    }

}

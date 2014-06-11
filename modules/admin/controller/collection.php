<?php

use Admin\Etc\Controller;
use THCFrame\Filesystem\ImageManager;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\ArrayMethods;

/**
 * 
 */
class Admin_Controller_Collection extends Controller
{

    /**
     * Action method returns list of all collections
     * 
     * @before _secured, _publisher
     */
    public function index()
    {
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
     * Action method shows and processes form used for new collection creation
     * 
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();
        $menu = App_Model_CollectionMenu::all(
                        array('active = ?' => true), array('id', 'title')
        );
        
        $view->set('menu', $menu);

        if (RequestMethods::post('submitAddCollection')) {
            $this->checkToken();

            $collection = new App_Model_Collection(array(
                'menuId' => RequestMethods::post('show'),
                'title' => RequestMethods::post('title'),
                'year' => RequestMethods::post('year', date('Y', time())),
                'season' => RequestMethods::post('season', ''),
                'date' => RequestMethods::post('date', date('Y-m-d', time())),
                'photographer' => RequestMethods::post('photographer', ''),
                'description' => RequestMethods::post('description', ''),
                'rank' => RequestMethods::post('rank', 1)
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
    }

    /**
     * Method shows detail of specific collection based on param id. 
     * From here can user upload photos and videos into collection.
     * 
     * @before _secured, _publisher
     * @param int $id   collection id
     */
    public function detail($id)
    {
        Event::fire('admin.log');
        $view = $this->getActionView();

        $collectionQuery = App_Model_Collection::getQuery(array('cl.*'))
                ->join('tb_collectionmenu', 'cl.menuId = m.id', 'm', 
                        array('m.title' => 'menuTitle'))
                ->join('tb_section', 'm.sectionId = s.id', 's', 
                        array('s.id' => 'sectId', 's.title' => 'sectionTitle'))
                ->where('cl.id = ?', $id);

        $collectionArray = App_Model_Collection::initialize($collectionQuery);
        $collection = array_shift($collectionArray);

        if (!empty($collection)) {
            $collectionPhotoCount = App_Model_CollectionPhoto::count(array('collectionId = ?' => $id));

            $query = App_Model_Photo::getQuery(array('ph.*'))
                    ->join('tb_collectionphoto', 'clp.photoId = ph.id', 'clp', 
                            array('clp.collectionId'))
                    ->where('clp.collectionId = ?', $id);
            $collectionPhotos = App_Model_Photo::initialize($query);

            $videoQuery = App_Model_Video::getQuery(array('vi.*'))
                    ->join('tb_collectionvideo', 'clv.videoId = vi.id', 'clv', 
                            array('clv.collectionId'))
                    ->where('clv.collectionId = ?', $id);
            $collectionVideos = App_Model_Video::initialize($videoQuery);

            $view->set('collection', $collection)
                    ->set('collectionphotocount', $collectionPhotoCount)
                    ->set('photos', $collectionPhotos)
                    ->set('videos', $collectionVideos);
        } else {
            $view->warningMessage('Collection not foud');
            self::redirect('/admin/collection/');
        }
    }

    /**
     * Action method shows and processes form used for editing specific 
     * collection based on param id
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
                        array('active = ?' => true), array('id', 'title')
        );
        
        $view->set('collection', $collection)
                ->set('menu', $menu);
        
        if (RequestMethods::post('submitEditCollection')) {
            $this->checkToken();

            $collection->title = RequestMethods::post('title');
            $collection->menuId = RequestMethods::post('show');
            $collection->active = RequestMethods::post('active');
            $collection->year = RequestMethods::post('year', date('Y', time()));
            $collection->season = RequestMethods::post('season', '');
            $collection->date = RequestMethods::post('date', date('Y-m-d', time()));
            $collection->photographer = RequestMethods::post('photographer', '');
            $collection->description = RequestMethods::post('description', '');
            $collection->rank = RequestMethods::post('rank', 1);

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
    }

    /**
     * Action method shows and processes form used for deleting specific 
     * collection based on param id. If is collection delete confirmed, 
     * there is option used for deleting all photos in collection.
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $collection = App_Model_Collection::first(
                        array('id = ?' => $id), 
                        array('id', 'title', 'created')
        );

        if (NULL === $collection) {
            $view->errorMessage('Collection not found');
            self::redirect('/admin/collection/');
        }

        $photoCount = App_Model_CollectionPhoto::count(array('collectionId = ?' => $collection->getId()));

        $view->set('collection', $collection)
                ->set('photocount', $photoCount);

        if (RequestMethods::post('submitDeleteCollection')) {
            $this->checkToken();

            if ($collection->delete()) {
                if (RequestMethods::post('action') == 1) {
                    rmdir(APP_PATH.'/public/uploads/images/collections/' . $collection->getId());
                }

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Collection has been deleted');
                self::redirect('/admin/collection/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->errorMessage('Unknown error eccured');
                self::redirect('/admin/collection/');
            }
        }
    }

    /**
     * Action method shows and processes form used for uploading photos into
     * collection specified by param id
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
            $this->checkToken();
            $errors = array();
            
            try {
                $uploadTo = 'collections/' . $id;
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
                var_dump($errors);
            }
        } elseif (RequestMethods::post('submitAddMultiPhoto')) {
            $this->checkToken();
            $errors = array();
            
            try {
                $uploadTo = 'collections/' . $id;
                $im = new ImageManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                    'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                    'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                ));

                $result = $im->upload('photos', $uploadTo);
            } catch (Exception $ex) {
                $errors['photo'] = $ex->getMessage();
            }

            if (is_array($result) && !empty($result['errors'])) {
                $errors['photo'] = $result['errors'];
            }

            if (is_array($result) && !empty($result)) {
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
     * Method is called via ajax and deletes photo specified by param id
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
     * Method is called via ajax and activate or deactivate photo specified by
     * param id
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

    /**
     * Method called via ajax checks if photo with specific filename already
     * exists or not
     * 
     * @before _secured, _publisher
     */
    public function checkPhoto()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $filename = pathinfo(RequestMethods::post('filename'), PATHINFO_FILENAME);
        $collectionId = RequestMethods::post('collectionId');

        $photoQuery = App_Model_Photo::getQuery(array('ph.photoName'))
                ->join('tb_collectionphoto', 'clp.photoId = ph.id', 'clp', 
                        array('clp.photoId', 'clp.collectionId'))
                ->where('clp.collectionId = ?', $collectionId)
                ->where('ph.photoName LIKE ?', $filename);

        $photo = App_Model_Photo::initialize($photoQuery);

        if ($photo === null) {
            echo 'ok';
        } else {
            echo "Photo with this name {$filename} already exits in collection. Do you want to overwrite it?";
        }
    }

    /**
     * Action method shows and processes form used for adding video into collection.
     * Manipulation with video is done via video controller.
     * 
     * @before _secured, _publisher
     * @param int $id   collection id
     */
    public function addVideo($id)
    {
        $view = $this->getActionView();

        $collection = App_Model_Collection::first(
                        array(
                    'id = ?' => $id,
                    'active = ?' => true
                        ), array('id', 'title')
        );

        $view->set('collection', $collection);

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

            if ($video->validate()) {
                $videoId = $video->save();

                $collectionvideo = new App_Model_CollectionVideo(array(
                    'videoId' => $videoId,
                    'collectionId' => (int) $id,
                ));
                $collectionvideo->save();

                Event::fire('admin.log', array('success', 'ID: ' . $videoId));
                $view->successMessage('Video has been successfully saved');
                self::redirect('/admin/collection/detail/' . $id);
            } else {
                $view->set('errors', $video->getErrors())
                        ->set('video', $video);
                Event::fire('admin.log', array('fail'));
            }
        }
    }

}

<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\ArrayMethods;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

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
                        array('active = ?' => true), 
                        array('id', 'title')
        );
        
        $view->set('menu', $menu)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddCollection')) {
            if($this->checkToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/collection/');
            }
            
            $cache = Registry::get('cache');

            $collection = new App_Model_Collection(array(
                'menuId' => RequestMethods::post('show'),
                'title' => RequestMethods::post('title'),
                'year' => RequestMethods::post('year', date('Y', time())),
                'season' => RequestMethods::post('season'),
                'date' => RequestMethods::post('date'),
                'photographer' => RequestMethods::post('photographer'),
                'description' => RequestMethods::post('description'),
                'rank' => RequestMethods::post('rank', 1)
            ));

            if ($collection->validate()) {
                $id = $collection->save();

                Event::fire('admin.log', array('success', 'Collection id: ' . $id));
                $view->successMessage('Collection'.self::SUCCESS_MESSAGE_1);
                $cache->invalidate();
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
                ->where('cl.id = ?', (int)$id);

        $collectionArray = App_Model_Collection::initialize($collectionQuery);
        $collection = array_shift($collectionArray);

        if (!empty($collection)) {
            $collectionPhotoCount = App_Model_CollectionPhoto::count(array('collectionId = ?' => (int)$id));

            $query = App_Model_Photo::getQuery(array('ph.*'))
                    ->join('tb_collectionphoto', 'clp.photoId = ph.id', 'clp', 
                            array('clp.collectionId'))
                    ->where('clp.collectionId = ?', $id)
                    ->order('ph.priority', 'desc')
                    ->order('ph.created', 'desc');
            $collectionPhotos = App_Model_Photo::initialize($query);

            $videoQuery = App_Model_Video::getQuery(array('vi.*'))
                    ->join('tb_collectionvideo', 'clv.videoId = vi.id', 'clv', 
                            array('clv.collectionId'))
                    ->where('clv.collectionId = ?', $id)
                    ->order('vi.priority', 'desc')
                    ->order('vi.created', 'desc');
            $collectionVideos = App_Model_Video::initialize($videoQuery);

            $view->set('collection', $collection)
                    ->set('collectionphotocount', $collectionPhotoCount)
                    ->set('photos', $collectionPhotos)
                    ->set('videos', $collectionVideos);
        } else {
            $view->warningMessage(self::ERROR_MESSAGE_2);
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

        $collection = App_Model_Collection::first(array('id = ?' => (int) $id));

        if (NULL === $collection) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/collection/');
        }

        $menu = App_Model_CollectionMenu::all(
                        array('active = ?' => true), 
                        array('id', 'title')
        );
        
        $view->set('collection', $collection)
                ->set('menu', $menu);
        
        if (RequestMethods::post('submitEditCollection')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/collection/');
            }
            
            $cache = Registry::get('cache');

            $collection->title = RequestMethods::post('title');
            $collection->menuId = RequestMethods::post('show');
            $collection->active = RequestMethods::post('active');
            $collection->year = RequestMethods::post('year', date('Y', time()));
            $collection->season = RequestMethods::post('season');
            $collection->date = RequestMethods::post('date');
            $collection->photographer = RequestMethods::post('photographer');
            $collection->description = RequestMethods::post('description');
            $collection->rank = RequestMethods::post('rank', 1);

            if ($collection->validate()) {
                $collection->save();

                Event::fire('admin.log', array('success', 'Collection id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                $cache->invalidate();
                self::redirect('/admin/collection/');
            } else {
                Event::fire('admin.log', array('fail', 'Collection id: ' . $id));
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
                        array('id = ?' => (int)$id), 
                        array('id', 'title', 'created')
        );

        if (NULL === $collection) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/collection/');
        }

        $colPhotos = App_Model_CollectionPhoto::all(array('collectionId = ?' => $collection->getId()));

        $view->set('collection', $collection)
                ->set('photocount', count($colPhotos));

        if (RequestMethods::post('submitDeleteCollection')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/collection/');
            }
            
            $cache = Registry::get('cache');

            if ($collection->delete()) {
                if (RequestMethods::post('action') == 1) {
                    $fm = new FileManager();
                    
                    $ids = array();
                    foreach ($colPhotos as $colPhoto) {
                        $ids[] = $colPhoto->getPhotoId();
                    }
                    
                    if(!empty($ids)){
                        App_Model_Photo::deleteAll(array('id IN ?' => $ids));
                    }
                    
                    $path = APP_PATH . '/public/uploads/images/collections/' . $collection->getId();
                    $fm->remove($path);
                }

                Event::fire('admin.log', array('success', 'Collection id: ' . $id));
                $view->successMessage('Collection'.self::SUCCESS_MESSAGE_3);
                $cache->invalidate();
                self::redirect('/admin/collection/');
            } else {
                Event::fire('admin.log', array('fail', 'Collection id: ' . $id));
                $view->errorMessage(self::ERROR_MESSAGE_1);
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
        $cache = Registry::get('cache');

        $collection = App_Model_Collection::first(
                        array('id = ?' => (int) $id, 'active = ?' => true), array('id', 'title')
        );

        if ($collection === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/collection/');
        }

        $view->set('collection', $collection)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        $fileManager = new FileManager(array(
            'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
            'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
            'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
            'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
            'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
        ));

        if (RequestMethods::post('submitAddPhoto')) {
            if($this->checkToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/collection/detail/'.$collection->getId());
            }
            
            $errors = array();

            try {
                $data = $fileManager->upload('photo', 'collections/' . $id);
                $uploaded = ArrayMethods::toObject($data);
            } catch (Exception $ex) {
                $errors['photo'] = array($ex->getMessage());
            }

            $photo = new App_Model_Photo(array(
                'description' => RequestMethods::post('description'),
                'category' => RequestMethods::post('category'),
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

            if (empty($errors) && $photo->validate()) {
                $photoId = $photo->save();

                $collectionPhoto = new App_Model_CollectionPhoto(array(
                    'photoId' => $photoId,
                    'collectionId' => $collection->getId(),
                ));

                $collectionPhoto->save();

                Event::fire('admin.log', array('success', 'Photo id: ' . $photoId . ' in collection ' . $collection->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_7);
                $cache->invalidate();
                self::redirect('/admin/collection/detail/' . $id);
            } else {
                Event::fire('admin.log', array('fail', 'Collection id: ' . $collection->getId()));
                $view->set('errors', $errors + $photo->getErrors());
            }
        } elseif (RequestMethods::post('submitAddMultiPhoto')) {
            $this->checkToken();
            $errors = array();

            try {
                $data = $fileManager->upload('photos', 'collections/' . $id);
            } catch (Exception $ex) {
                $errors['photos'] = array($ex->getMessage());
            }

            if (is_array($data) && !empty($data['errors'])) {
                $errors['photos'] = $data['errors'];
            }

            if (is_array($data) && !empty($data)) {
                foreach ($data['files'] as $image) {
                    $object = ArrayMethods::toObject($image);

                    $photo = new App_Model_Photo(array(
                        'description' => RequestMethods::post('description'),
                        'category' => RequestMethods::post('category'),
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

                    if (empty($errors) && $photo->validate()) {
                        $photoId = $photo->save();

                        $collectionPhoto = new App_Model_CollectionPhoto(array(
                            'photoId' => $photoId,
                            'collectionId' => $collection->getId()
                        ));

                        $collectionPhoto->save();

                        Event::fire('admin.log', array('success', 'Photo id: ' . $photoId . ' in collection ' . $collection->getId()));
                    } else {
                        Event::fire('admin.log', array('fail', 'Collection id: ' . $collection->getId()));
                        $errors += $photo->getErrors();
                    }
                }

                if (empty($errors)) {
                    $view->successMessage(self::SUCCESS_MESSAGE_7);
                    $cache->invalidate();
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

        if ($this->checkToken()) {
            $photo = App_Model_Photo::first(
                            array('id = ?' => (int) $id), 
                            array('id', 'path', 'thumbPath')
            );

            if (null === $photo) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if (@unlink($photo->getUnlinkPath()) && @unlink($photo->getUnlinkThumbPath()) && $photo->delete()) {
                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'Photo id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
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

        $photo = App_Model_Photo::first(array('id = ?' => (int)$id));

        if (null === $photo) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if (!$photo->active) {
                $photo->active = true;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    echo 'active';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            } elseif ($photo->active) {
                $photo->active = false;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
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
        
        if($collection === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/collection/');
        }

        $view->set('collection', $collection);

        if (RequestMethods::post('submitAddVideo')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/collection/');
            }
            
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

                Event::fire('admin.log', array('success', 'Video id: ' . $videoId. 'in collection '. $id));
                $view->successMessage(self::SUCCESS_MESSAGE_9);
                self::redirect('/admin/collection/detail/' . $id);
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $video->getErrors())
                        ->set('video', $video);
                
            }
        }
    }

}

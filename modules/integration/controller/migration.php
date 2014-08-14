<?php

use Integration\Etc\Controller;
use THCFrame\Database\Database;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\FileManager;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\StringMethods;

/**
 * Description of Integration_Controller_Cron
 *
 * @author Tomy
 */
class Integration_Controller_Migration extends Controller
{

    /**
     * @readwrite
     */
    protected $_fileManager;

    /**
     * @readwrite
     */
    protected $_pathToImages;

    /**
     * @readwrite
     */
    protected $_db;
    private $_statusInfo = array();

    /**
     * @before _secured, _superadmin
     */
    private function connectToLiveDb()
    {
        $database = new Database();
        $database->type = 'mysql';
        $database->options = array(
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'schema' => 'markoin_db'
        );

        return $database->initialize();
    }

    /**
     * 
     * @return type
     */
    private function getPathToImages()
    {
        if (is_dir('/' . $this->_pathToImages)) {
            return '/' . $this->_pathToImages;
        } elseif (is_dir('./' . $this->_pathToImages)) {
            return './' . $this->_pathToImages;
        } elseif (is_dir(APP_PATH . '/' . $this->_pathToImages)) {
            return APP_PATH . '/' . $this->_pathToImages;
        }
    }

    /**
     * @before _secured, _superadmin
     */
    private function getCollections()
    {
        // ini_set('default_charset', 'UTF-8');
        $errors = array();
        $colMigrated = array();
        $colPhotoMigrated = 0;

        $sql = 'SELECT * FROM tb_collections ORDER BY added ASC';
        $result = $this->db->execute($sql);

        while ($row = $result->fetch_assoc()) {
            $colId = $row['collection_id'];

            if (strtolower(html_entity_decode($row['collection_show_in'], ENT_QUOTES, 'UTF-8')) == 'kolekce') {
                $menuId = 4;
            } elseif (strtolower(html_entity_decode($row['collection_show_in'], ENT_QUOTES, 'UTF-8')) == 'módní exhibice') {
                $menuId = 5;
            } elseif (strtolower(html_entity_decode($row['collection_show_in'], ENT_QUOTES, 'UTF-8')) == 'módní přehlídky') {
                $menuId = 6;
            } else {
                $menuId = 4;
            }

            $collection = new App_Model_Collection(array(
                'menuId' => $menuId,
                'active' => (boolean) $row['collection_active'],
                'title' => html_entity_decode($row['collection_name'], ENT_QUOTES, 'UTF-8'),
                'date' => date('Y-m-d', strtotime(str_replace(' ', '', $row['date']))),
                'year' => $row['year'],
                'season' => html_entity_decode($row['season'], ENT_QUOTES, 'UTF-8'),
                'description' => html_entity_decode($row['collection_description'], ENT_QUOTES, 'UTF-8'),
                'photographer' => html_entity_decode($row['photographer'], ENT_QUOTES, 'UTF-8'),
                'rank' => 1
            ));

            if ($collection->validate()) {
                $newCollectionId = $collection->save();
                $colMigrated[] = $collection->getTitle();

                $path = $this->getPathToImages() . '/collections/' . $newCollectionId;
                if (!is_dir($path)) {
                    $this->fileManager->mkdir($path, 0755);
                }

                $sql2 = "SELECT * FROM tb_collection_photos WHERE collection_id ='{$colId}' ORDER BY added ASC";
                $result2 = $this->db->execute($sql2);

                while ($photoR = $result2->fetch_assoc()) {
                    if ($photoR['ext'] == 'png') {
                        $mime = 'image/png';
                    } elseif ($photoR['ext'] == 'gif') {
                        $mime = 'image/gif';
                    } else {
                        $mime = 'image/jpeg';
                    }

                    $rawPhotoName = StringMethods::removeDiacriticalMarks(
                                    str_replace(' ', '_', html_entity_decode($row['photo_name'], ENT_QUOTES, 'UTF-8')
                                    )
                    );

                    $rawThumbPath = $path . '/' . $rawPhotoName . '_small.' . $photoR['ext'];
                    $rawPath = $path . '/' . $rawPhotoName . '_large.' . $photoR['ext'];
                    
                    $oldThumbPath = html_entity_decode($photoR['path_small'], ENT_QUOTES, 'UTF-8');
                    $oldPath = html_entity_decode($photoR['path_large'], ENT_QUOTES, 'UTF-8');
                    
                    if(!file_exists('.'.$oldPath)){
                        continue;
                    }

                    $photo = new App_Model_Photo(array(
                        'active' => (boolean) $photoR['photo_active'],
                        'photoName' => html_entity_decode($photoR['photo_name'], ENT_QUOTES, 'UTF-8'),
                        'thumbPath' => trim($rawThumbPath, '.'),
                        'path' => trim($rawPath, '.'),
                        'description' => html_entity_decode($photoR['title'], ENT_QUOTES, 'UTF-8'),
                        'category' => html_entity_decode($photoR['sub_type'], ENT_QUOTES, 'UTF-8'),
                        'priority' => 0,
                        'mime' => $mime,
                        'size' => $photoR['size'] * 1024,
                        'width' => 1,
                        'height' => 1,
                        'thumbSize' => 1,
                        'thumbWidth' => 1,
                        'thumbHeight' => 1
                    ));

                    if ($photo->validate()) {
                        $newPhotoId = $photo->save();
                        $colPhotoMigrated++;

                        $colPhoto = new App_Model_CollectionPhoto(array(
                            'photoId' => $newPhotoId,
                            'collectionId' => $newCollectionId
                        ));

                        $colPhoto->save();

                        try {
                            $this->fileManager->copy('.' . $oldThumbPath, $rawThumbPath);
                            $this->fileManager->copy('.' . $oldPath, $rawPath);
                        } catch (Exception $ex) {
                            $errors['collection_' . $newCollectionId . '_photo_copy'] = $ex->getMessage();
                        }
                    } else {
                        $errors['collection_' . $newCollectionId . '_photo'] = $photo->getErrors();
                    }
                }
            } else {
                $errors['collection_'] = $collection->getErrors();
            }
        }

        $this->_statusInfo['colMigrated'] = $colMigrated;
        $this->_statusInfo['colPhotoMigrated'] = $colPhotoMigrated;

        if (empty($errors)) {
            return true;
        } else {
            return $errors;
        }
    }

    /**
     * @before _secured, _superadmin
     */
    private function getPhotos()
    {
        $errors = array();
        $photosMigrated = array();

        $sql = 'SELECT * FROM tb_page_photos ORDER BY section ASC';
        $result = $this->db->execute($sql);

        while ($row = $result->fetch_assoc()) {
            if ($row['ext'] === null) {
                $ext = pathinfo('.' . $row['path_small'], PATHINFO_EXTENSION);
                $row['ext'] = $ext;
            }

            if ($row['ext'] == 'png') {
                $mime = 'image/png';
            } elseif ($row['ext'] == 'gif') {
                $mime = 'image/gif';
            } else {
                $mime = 'image/jpeg';
            }

            $rawPhotoName = StringMethods::removeDiacriticalMarks(
                            str_replace(' ', '_', html_entity_decode($row['photo_name'], ENT_QUOTES, 'UTF-8')
                            )
            );

            if ($row['section'] == 'portfolio') {
                $colId = 1;
                $rawThumbPath = $this->getPathToImages() . '/collections/1/' . $rawPhotoName . '_small.' . $row['ext'];
                $rawPath = $this->getPathToImages() . '/collections/1/' . $rawPhotoName . '_large.' . $row['ext'];
            } elseif ($row['section'] == 'flattus') {
                $colId = 2;
                $rawThumbPath = $this->getPathToImages() . '/collections/2/' . $rawPhotoName . '_small.' . $row['ext'];
                $rawPath = $this->getPathToImages() . '/collections/2/' . $rawPhotoName . '_large.' . $row['ext'];
            } elseif ($row['section'] == 'magazin') {
                $colId = 3;
                $rawThumbPath = $this->getPathToImages() . '/collections/3/' . $rawPhotoName . '_small.' . $row['ext'];
                $rawPath = $this->getPathToImages() . '/collections/3/' . $rawPhotoName . '_large.' . $row['ext'];
            } else {
                $colId = 0;
                $rawThumbPath = $this->getPathToImages() . '/section_photos/' . $rawPhotoName . '_small.' . $row['ext'];
                $rawPath = $this->getPathToImages() . '/section_photos/' . $rawPhotoName . '_large.' . $row['ext'];
            }

            if (!is_dir($this->getPathToImages() . '/section_photos')) {
                $this->fileManager->mkdir($this->getPathToImages() . '/section_photos', 0755);
            }
            
            $oldThumbPath = html_entity_decode($row['path_small'], ENT_QUOTES, 'UTF-8');
            $oldPath = html_entity_decode($row['path_large'], ENT_QUOTES, 'UTF-8');
            
            if(!file_exists('.'.$oldPath)){
                continue;
            }

            $photo = new App_Model_Photo(array(
                'active' => (boolean) $row['photo_active'],
                'photoName' => $rawPhotoName,
                'thumbPath' => trim($rawThumbPath, '.'),
                'path' => trim($rawPath, '.'),
                'description' => html_entity_decode($row['title'], ENT_QUOTES, 'UTF-8'),
                'category' => html_entity_decode($row['sub_type'], ENT_QUOTES, 'UTF-8'),
                'priority' => (int) $row['priority'],
                'mime' => $mime,
                'size' => 1,
                'width' => 1,
                'height' => 1,
                'thumbSize' => 1,
                'thumbWidth' => 1,
                'thumbHeight' => 1
            ));

            if ($photo->validate()) {
                $newPhotoId = $photo->save();
                $photosMigrated[] = $photo->getPath();

                if ($colId != 0) {
                    $collectionPhoto = new App_Model_CollectionPhoto(array(
                        'photoId' => $newPhotoId,
                        'collectionId' => $colId
                    ));
                    $collectionPhoto->save();
                } else {
                    $section = App_Model_Section::first(
                                    array('urlKey = ?' => $row['section']), array('id')
                    );

                    if ($section !== null) {
                        $sectionPhoto = new App_Model_PhotoSection(array(
                            'photoId' => $newPhotoId,
                            'sectionId' => $section->getId()
                        ));
                        $sectionPhoto->save();
                    }
                }

                try {
                    $this->fileManager->copy('.'.$oldThumbPath, $rawThumbPath);
                    $this->fileManager->copy('.'.$oldPath, $rawPath);
                } catch (Exception $ex) {
                    $errors[] = $ex->getMessage();
                }
            } else {
                $errors[] = $photo->getErrors();
            }
        }

        $this->_statusInfo['photoMigrated'] = $photosMigrated;

        if (empty($errors)) {
            return true;
        } else {
            return $errors;
        }
    }

    /**
     * @before _secured, _superadmin
     */
    private function getVideos()
    {
        $errors = array();
        $videosMigrated = array();

        $sql = 'SELECT * FROM tb_videos ORDER BY section ASC';
        $result = $this->db->execute($sql);

        while ($row = $result->fetch_assoc()) {
            $video = new App_Model_Video(array(
                'active' => (boolean) $row['selected'],
                'title' => html_entity_decode($row['title'], ENT_QUOTES, 'UTF-8'),
                'path' => html_entity_decode($row['video_path'], ENT_QUOTES, 'UTF-8'),
                'width' => (int) $row['video_width'],
                'height' => (int) $row['video_height'],
                'priority' => (int) $row['priority'],
            ));

            if ($video->validate()) {
                $newVideoId = $video->save();
                $videosMigrated[] = $video->getPath();

                $section = App_Model_Section::first(
                                array('urlKey = ?' => $row['section']), array('id')
                );

                $videoSection = new App_Model_VideoSection(array(
                    'videoId' => $newVideoId,
                    'sectionId' => $section->getId()
                ));
                $videoSection->save();
            } else {
                $errors[] = $video->getErrors();
            }
        }

        $this->_statusInfo['videoMigrated'] = $videosMigrated;

        if (empty($errors)) {
            return true;
        } else {
            return $errors;
        }
    }

    /**
     * @before _secured, _superadmin
     */
    private function prepareDirs()
    {
        if (!is_dir($this->getPathToImages() . '/section_photos')) {
            $this->fileManager->mkdir($this->getPathToImages() . '/section_photos', 0755);
        }

        if (!is_dir($this->getPathToImages() . '/collections/1')) {
            $this->fileManager->mkdir($this->getPathToImages() . '/collections/1', 0755);
        }

        if (!is_dir($this->getPathToImages() . '/collections/2')) {
            $this->fileManager->mkdir($this->getPathToImages() . '/collections/2', 0755);
        }

        if (!is_dir($this->getPathToImages() . '/collections/3')) {
            $this->fileManager->mkdir($this->getPathToImages() . '/collections/3', 0755);
        }
        
        if (!is_dir($this->getPathToImages() . '/collections/4')) {
            $this->fileManager->mkdir($this->getPathToImages() . '/collections/4', 0755);
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function migrate()
    {
        $view = $this->getActionView();
        $configuration = Registry::get('config');

        if (!empty($configuration->files)) {
            $this->_pathToImages = trim($configuration->files->pathToImages, '/');
        } else {
            throw new \Exception('Error in configuration file');
        }

        $this->fileManager = new FileManager();

        if (RequestMethods::post('migrateCollections')) {
            
            $db = $this->connectToLiveDb();
            $this->db = $db->connect();

            $this->prepareDirs();
            $resultVid = $this->getVideos();
            $resultPho = $this->getPhotos();
            $resultCol = $this->getCollections();

            if (is_array($resultCol) || is_array($resultPho) || is_array($resultVid)) {
                $result = array_merge($resultCol, $resultPho, $resultVid);
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $result);
            } elseif ($resultCol == true && $resultPho == true && $resultVid == true) {
                Event::fire('admin.log', array('success'));

                $str = 'Migrated collections: ' . join(', ', $this->_statusInfo['colMigrated']) . '<br/>';
                $str .= 'Migrated photos into collections: ' . $this->_statusInfo['colPhotoMigrated'] . '<br/>';
                $str .= '<hr/><br/>Migrated section photos: ' . count($this->_statusInfo['photoMigrated']) . '<br/>';
                $str .= join('<br/>', $this->_statusInfo['photoMigrated']) . '<br/>';
                $str .= '<hr/><br/>Migrated videos: ' . count($this->_statusInfo['videoMigrated']) . '<br/>';
                $str .= join('<br/>', $this->_statusInfo['videoMigrated']);

                $view->set('success', 'Migration has been successfully completed')
                        ->set('status', $str);
            } else {
                Event::fire('admin.log', array('fail', 'An error occured'));
                $view->set('unknown', 'An error occured');
            }
        }
    }

}

<?php

use Integration\Etc\Controller;
use THCFrame\Database\Database;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\FileManager;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

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
     * @before _secured, _superadmin
     */
    private function getCollections()
    {
        ini_set('default_charset', 'UTF-8');
        $errors = array();
        $info = array('new_collections' => 0, 'new_photos' => 0);
        $db = $this->connectToLiveDb();
        $db->connect();

        $sql = 'SELECT * FROM tb_collections ORDER BY added ASC';
        $result = $db->execute($sql);

        while ($row = $result->fetch_assoc()) {
            $colId = $row['collection_id'];

            if (strtolower(html_entity_decode($row['collection_show_in'])) == 'kolekce') {
                $menuId = 4;
            } elseif (strtolower(html_entity_decode($row['collection_show_in'])) == 'módní exhibice') {
                $menuId = 5;
            } elseif (strtolower(html_entity_decode($row['collection_show_in'])) == 'módní přehlídky') {
                $menuId = 6;
            } else {
                $menuId = 4;
            }

            $collection = new App_Model_Collection(array(
                'menuId' => $menuId,
                'active' => (boolean) $row['collection_active'],
                'title' => html_entity_decode($row['collection_name']),
                'date' => date('Y-m-d', strtotime(str_replace(' ', '', $row['date']))),
                'year' => $row['year'],
                'season' => html_entity_decode($row['season']),
                'description' => html_entity_decode($row['collection_description']),
                'photographer' => html_entity_decode($row['photographer']),
                'rank' => 1
            ));

            if ($collection->validate()) {
                $newCollectionId = $collection->save();
                $info['new_collections'] += 1;

                $path = $this->getPathToImages() . '/collections/' . $newCollectionId;
                if (!is_dir('.' . $path)) {
                    $this->fileManager->mkdir('.' . $path, 0666, true);
                }

                $sql2 = 'SELECT * FROM tb_collection_photos WHERE collection_id =' . $colId . ' ORDER BY added ASC';
                $result2 = $db->execute($sql2);

                while ($photoR = $result2->fetch_assoc()) {
                    if ($photoR['ext'] == 'png') {
                        $mime = 'image/png';
                    } elseif ($photoR['ext'] == 'gif') {
                        $mime = 'image/gif';
                    } else {
                        $mime = 'image/jpeg';
                    }

                    $photo = new App_Model_Photo(array(
                        'active' => (boolean) $photoR['photo_active'],
                        'photoName' => html_entity_decode($photoR['photo_name']),
                        'thumbPath' => html_entity_decode($photoR['path_small']),
                        'path' => html_entity_decode($photoR['path_large']),
                        'description' => html_entity_decode($photoR['title']),
                        'category' => html_entity_decode($photoR['sub_type']),
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
                        $info['new_photos'] += 1;

                        $colPhoto = new App_Model_CollectionPhoto(array(
                            'photoId' => $newPhotoId,
                            'collectionId' => $newCollectionId
                        ));

                        $colPhoto->save();
                    } else {
                        $errors['collection_' . $newCollectionId . '_photo'] = $photo->getErrors();
                    }
                }
            } else {
                $errors['collection_'] = $collection->getErrors();
            }
        }

        $this->_statusInfo = $info;

        if (empty($errors)) {
            return true;
        } else {
            return $errors;
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function migrateCollections()
    {
        $view = $this->getActionView();

        $configuration = Registry::get('config');

        if (!empty($configuration->files)) {
            $this->_pathToImages = $configuration->files->pathToImages;
        } else {
            throw new \Exception('Error in configuration file');
        }

        $this->fileManager = new FileManager();

        if (RequestMethods::post('migrateCollections')) {
            $result = $this->getCollections();

            if (is_array($result)) {
                Event::fire('admin.log', array('fail', 'Error Count: ' . count($result)));
                $view->set('errors', $result);
            } elseif (is_bool($result)) {
                Event::fire('admin.log', array('success', 'New collections: ' .
                    $this->_statusInfo['new_collections'] . ' / New photos: ' . $this->_statusInfo['new_photos']));
                $view->set('success', 'Migration has been successfully completed')
                        ->set('status', $this->_statusInfo);
            } else {
                Event::fire('admin.log', array('fail', 'An error occured'));
                $view->set('unknown', 'An error occured');
            }
        }
    }

}

<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class Admin_Controller_CollectionMenu extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $clmenuQuery = App_Model_CollectionMenu::getQuery(array('clm.*'))
                ->join('tb_section', 'clm.sectionId = s.id', 's', 
                        array('s.title' => 'secTitle'));
        $clmenu = App_Model_CollectionMenu::initialize($clmenuQuery);
        
        $view->set('clmenu', $clmenu);
    }

    /**
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();

        $sections = App_Model_Section::all(
                        array(
                    'active = ?' => true,
                    'supportCollection = ?' => true
                        ), array('id', 'urlKey', 'title')
        );
        
        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddClmenu')) {
            $this->checkToken();
            $urlKey = StringMethods::removeDiacriticalMarks(RequestMethods::post('urlkey'));
            
            $clm = new App_Model_CollectionMenu(array(
                'sectionId' => RequestMethods::post('section'),
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey,
                'customName' => RequestMethods::post('custom', ''),
                'rank' => RequestMethods::post('rank', 1)
            ));

            if ($clm->validate()) {
                $id = $clm->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Item has been successfully saved');
                self::redirect('/admin/collectionmenu/');
            } else {
                $view->set('errors', $clm->getErrors())
                        ->set('clmenu', $clm);
                Event::fire('admin.log', array('fail'));
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

        $sections = App_Model_Section::all(
                        array(
                    'active = ?' => true,
                    'supportCollection = ?' => true
                        ), array('id', 'urlKey', 'title')
        );

        $clm = App_Model_CollectionMenu::first(array('id = ?' => $id));

        if (NULL === $clm) {
            $view->errorMessage('Item not found');
            self::redirect('/admin/collectionmenu/');
        }
        
        $view->set('clmenu', $clm)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditClmenu')) {
            $this->checkToken();
            $urlKey = StringMethods::removeDiacriticalMarks(RequestMethods::post('urlkey'));

            $clm->sectionId = RequestMethods::post('section');
            $clm->title = RequestMethods::post('title');
            $clm->urlKey = $urlKey;
            $clm->customName = RequestMethods::post('custom', '');
            $clm->rank = RequestMethods::post('rank', 1);
            $clm->active = RequestMethods::post('active');

            if ($clm->validate()) {
                $clm->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/collectionmenu/');
            } else {
                $view->set('errors', $clm->getErrors());
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
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
        $this->checkToken();
        
        $clm = App_Model_CollectionMenu::first(
                        array('id = ?' => $id), array('id')
        );

        if (NULL === $clm) {
            echo 'Item not found';
        } else {
            if ($clm->delete()) {
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

        if (RequestMethods::post('performClmAction')) {
            $this->checkToken();
            $ids = RequestMethods::post('clmids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $clms = App_Model_CollectionMenu::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($clms as $clm) {
                        if (NULL !== $clm) {
                            if (!$clm->delete()) {
                                $errors[] = 'An error occured while deleting ' . $clm->getTitle();
                            }
                        } else {
                            $errors[] = "Item with id {$clm->getId()} not found<br/>";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Items have been deleted');
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/collectionmenu/');

                    break;
                case 'activate':
                    $clms = App_Model_CollectionMenu::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($clms as $clm) {
                        if (NULL !== $clm) {
                            $clm->active = true;

                            if ($clm->validate()) {
                                $clm->save();
                            } else {
                                $errors[] = "Item id {$clm->getId()} - {$clm->getTitle()} errors: " 
                                          . join(', ', $clm->getErrors());
                            }
                        } else {
                            $errors[] = "Item with id {$clm->getId()} not found";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Items have been activated');
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/collectionmenu/');

                    break;
                case 'deactivate':
                    $clms = App_Model_CollectionMenu::all(array(
                                'id IN ?' => $ids
                    ));

                    foreach ($clms as $clm) {
                        if (NULL !== $clm) {
                            $clm->active = false;

                            if ($clm->validate()) {
                                $clm->save();
                            } else {
                                $errors[] = "Item id {$clm->getId()} - {$clm->getTitle()} errors: " 
                                          . join(', ', $clm->getErrors());
                            }
                        } else {
                            $errors[] = "Item with id {$clm->getId()} not found";
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'IDs: ' . join(',', $ids)));
                        $view->successMessage('Items have been deactivated');
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/collectionmenu/');
                    break;
                default:
                    self::redirect('/admin/collectionmenu/');
                    break;
            }
        }
    }
}

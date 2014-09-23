<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_CollectionMenu extends Controller
{
    
    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_CollectionMenu::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }
    
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

        $view->set('sections', $sections)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddClmenu')) {
            if($this->checkToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/collectionmenu/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('urlkey'));

            if(!$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }
            
            $clm = new App_Model_CollectionMenu(array(
                'sectionId' => RequestMethods::post('section'),
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey,
                'customName' => RequestMethods::post('custom'),
                'rank' => RequestMethods::post('rank', 1)
            ));

            if (empty($errors) && $clm->validate()) {
                $id = $clm->save();

                Event::fire('admin.log', array('success', 'Collection menu id: ' . $id));
                $view->successMessage('Item'.self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/collectionmenu/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $clm->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('clmenu', $clm);
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
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/collectionmenu/');
        }

        $view->set('clmenu', $clm)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditClmenu')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/collectionmenu/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('urlkey'));
            
            if($clm->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }

            $clm->sectionId = RequestMethods::post('section');
            $clm->title = RequestMethods::post('title');
            $clm->urlKey = $urlKey;
            $clm->customName = RequestMethods::post('custom');
            $clm->rank = RequestMethods::post('rank', 1);
            $clm->active = RequestMethods::post('active');

            if (empty($errors) && $clm->validate()) {
                $clm->save();

                Event::fire('admin.log', array('success', 'Collection menu id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/collectionmenu/');
            } else {
                Event::fire('admin.log', array('fail', 'Collection menu id: ' . $id));
                $view->set('errors', $errors + $clm->getErrors());
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
        
        if ($this->checkToken()) {
            $clm = App_Model_CollectionMenu::first(
                            array('id = ?' => $id), array('id')
            );

            if ($clm === null) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($clm->delete()) {
                    Event::fire('admin.log', array('success', 'Collection menu id: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'Collection menu id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
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
            if($this->checkToken() !== true){
                self::redirect('/admin/collectionmenu/');
            }
            
            $ids = RequestMethods::post('clmids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $clms = App_Model_CollectionMenu::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $clms) {
                        foreach ($clms as $clm) {

                            if (!$clm->delete()) {
                                $errors[] = 'An error occured while deleting ' . $clm->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Collection menu ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
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

                    if (NULL !== $clms) {
                        foreach ($clms as $clm) {
                            $clm->active = true;

                            if ($clm->validate()) {
                                $clm->save();
                            } else {
                                $errors[] = "Item id {$clm->getId()} - {$clm->getTitle()} errors: "
                                        . join(', ', $clm->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Collection menu ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
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
                    if (NULL !== $clms) {
                        foreach ($clms as $clm) {
                            $clm->active = false;

                            if ($clm->validate()) {
                                $clm->save();
                            } else {
                                $errors[] = "Item id {$clm->getId()} - {$clm->getTitle()} errors: "
                                        . join(', ', $clm->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Collection menu ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
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

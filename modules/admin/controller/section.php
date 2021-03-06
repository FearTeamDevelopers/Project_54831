<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Section extends Controller
{
    
    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Section::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * @before _secured, _superadmin
     */
    public function index()
    {
        $view = $this->getActionView();

        $sections = App_Model_Section::all();

        $view->set('sections', $sections);
    }

    /**
     * @before _secured, _superadmin
     */
    public function add()
    {
        $view = $this->getActionView();
        $sections = App_Model_Section::all(
                    array('active = ?' => true), 
                    array('id', 'parentId', 'title')
        );

        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddSection')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/admin/section/');
            }
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if(!$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }
            
            $section = new App_Model_Section(array(
                'parentId' => RequestMethods::post('parent', 1),
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey,
                'rank' => RequestMethods::post('rank', 1),
                'supportVideo' => RequestMethods::post('supportVideo', 0),
                'supportPhoto' => RequestMethods::post('supportPhoto', 0),
                'supportCollection' => RequestMethods::post('supportCollection', 0)
            ));

            if (empty($errors) && $section->validate()) {
                $section->save();

                $view->flashMessage('Section has been successfully saved');
                self::redirect('/admin/section/');
            } else {
                $view->set('errors', $errors + $section->getErrors())
                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $sections = App_Model_Section::all(
                    array('active = ?' => true), 
                    array('id', 'parentId', 'title')
        );

        $section = App_Model_Section::first(array('id = ?' => (int)$id));

        if (NULL === $section) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/section/');
        }

        $view->set('section', $section)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditSection')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/admin/section/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));
            
            if($section->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)){
                $errors['title'] = array('This title is already used');
            }

            $section->parentId = RequestMethods::post('partner', 1);
            $section->title = RequestMethods::post('title');
            $section->urlKey = $urlKey;
            $section->rank = RequestMethods::post('rank', 1);
            $section->supportVideo = RequestMethods::post('supportVideo', 0);
            $section->supportPhoto = RequestMethods::post('supportPhoto', 0);
            $section->supportCollection = RequestMethods::post('supportCollection', 0);
            $section->active = RequestMethods::post('active');

            if (empty($errors) && $section->validate()) {
                $section->save();

                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/section/');
            } else {
                $view->set('errors', $errors + $section->getErrors());
            }
        }
    }

}

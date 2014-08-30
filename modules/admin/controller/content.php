<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Content extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $content = App_Model_PageContent::all();

        $view->set('content', $content);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $sections = App_Model_Section::all(array(
                    'active = ?' => true
                        ), array('id', 'parentId', 'title')
        );
        
        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddContent')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/content/');
            }
            
            $cache = Registry::get('cache');

            $content = new App_Model_PageContent(array(
                'sectionId' => RequestMethods::post('section'),
                'pageName' => RequestMethods::post('page', ''),
                'body' => RequestMethods::post('text', ''),
                'bodyEn' => RequestMethods::post('texten', '')
            ));

            if ($content->validate()) {
                $id = $content->save();

                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage('Content'.self::SUCCESS_MESSAGE_1);
                $cache->invalidate();
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $content->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $sections = App_Model_Section::all(array(
                    'active = ?' => true
                        ), array('id', 'title')
        );

        $content = App_Model_PageContent::first(array('id = ?' => (int)$id));

        if (NULL === $content) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/content/');
        }
        
        $view->set('sections', $sections)
                ->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/content/');
            }
            
            $cache = Registry::get('cache');
            
            $content->sectionId = RequestMethods::post('section');
            $content->pageName = RequestMethods::post('page', '');
            $content->body = RequestMethods::post('text', '');
            $content->bodyEn = RequestMethods::post('texten', '');
            $content->active = RequestMethods::post('active');

            if ($content->validate()) {
                $content->save();

                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                $cache->invalidate();
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'Content id: ' . $id));
                $view->set('errors', $content->getErrors());
            }
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $content = App_Model_PageContent::first(
                        array('id = ?' => (int)$id), 
                        array('id', 'pageName', 'body')
        );

        if (NULL === $content) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/content/');
        }

        $view->set('content', $content);

        if (RequestMethods::post('submitDeleteContent')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/content/');
            }

            if ($content->delete()) {
                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage('Content'.self::SUCCESS_MESSAGE_3);
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'Content id: ' . $id));
                $view->errorMessage(self::ERROR_MESSAGE_1);
                self::redirect('/admin/content/');
            }
        } elseif (RequestMethods::post('cancel')) {
            self::redirect('/admin/content/');
        }
    }

}

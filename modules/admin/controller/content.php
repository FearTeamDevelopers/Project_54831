<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

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
            $this->checkToken();

            $content = new App_Model_PageContent(array(
                'sectionId' => RequestMethods::post('section'),
                'pageName' => RequestMethods::post('page', ''),
                'body' => RequestMethods::post('text', ''),
                'bodyEn' => RequestMethods::post('texten', '')
            ));

            if ($content->validate()) {
                $id = $content->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Content has been successfully saved');
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

        $content = App_Model_PageContent::first(array(
                    'id = ?' => $id
        ));

        if (NULL === $content) {
            $view->errorMessage('Content not found');
            self::redirect('/admin/content/');
        }
        
        $view->set('sections', $sections)
                ->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            $this->checkToken();
            
            $content->sectionId = RequestMethods::post('section');
            $content->pageName = RequestMethods::post('page', '');
            $content->body = RequestMethods::post('text', '');
            $content->bodyEn = RequestMethods::post('texten', '');
            $content->active = RequestMethods::post('active');

            if ($content->validate()) {
                $content->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
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

        $content = App_Model_PageContent::first(array(
                    'id = ?' => $id
                        ), array('id', 'pageName', 'body')
        );

        if (NULL === $content) {
            $view->errorMessage('Content not found');
            self::redirect('/admin/content/');
        }

        $view->set('content', $content);

        if (RequestMethods::post('submitDeleteContent')) {
            $this->checkToken();

            if ($content->delete()) {
                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Content has been deleted');
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->errorMessage('Unknown error eccured');
                self::redirect('/admin/content/');
            }
        } elseif (RequestMethods::post('cancel')) {
            self::redirect('/admin/content/');
        }
    }

}

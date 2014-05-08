<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods as RequestMethods;

/**
 * 
 */
class Admin_Controller_Section extends Controller
{

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

        if (RequestMethods::post('submitAddSection')) {
            $section = new App_Model_Section(array(
                'parentId' => RequestMethods::post('parent'),
                'title' => RequestMethods::post('title'),
                'rank' => RequestMethods::post('rank', 1),
                'supportVideo' => RequestMethods::post('supportVideo', 0),
                'supportPhoto' => RequestMethods::post('supportPhoto', 0)
            ));

            if ($section->validate()) {
                $section->save();

                $view->flashMessage('Section has been successfully saved');
                self::redirect('/admin/section/');
            } else {
                $view->set('errors', $section->getErrors());
            }
        }

        $view->set('sections', $sections);
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

        $section = App_Model_Section::first(array(
                    'id = ?' => $id
        ));

        if (NULL === $section) {
            $view->errorMessage('Section not found');
            self::redirect('/admin/section/');
        }

        if (RequestMethods::post('submitEditSection')) {
            $section->parentId = RequestMethods::post('partner');
            $section->title = RequestMethods::post('title');
            $section->rank = RequestMethods::post('rank');
            $section->supportVideo = RequestMethods::post('supportVideo');
            $section->supportPhoto = RequestMethods::post('supportPhoto');
            $section->active = RequestMethods::post('active');

            if ($section->validate()) {
                $section->save();

                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/section/');
            } else {
                $view->set('errors', $section->getErrors());
            }
        }
        $view->set('section', $section)
             ->set('sections', $sections);
    }

}

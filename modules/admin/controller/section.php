<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;

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

        $view->set('sections', $sections);

        if (RequestMethods::post('submitAddSection')) {
            $this->checkToken();

            $section = new App_Model_Section(array(
                'parentId' => RequestMethods::post('parent', 1),
                'title' => RequestMethods::post('title'),
                'rank' => RequestMethods::post('rank', 1),
                'supportVideo' => RequestMethods::post('supportVideo', 0),
                'supportPhoto' => RequestMethods::post('supportPhoto', 0),
                'supportCollection' => RequestMethods::post('supportCollection', 0)
            ));

            if ($section->validate()) {
                $section->save();

                $view->flashMessage('Section has been successfully saved');
                self::redirect('/admin/section/');
            } else {
                $view->set('errors', $section->getErrors());
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

        $section = App_Model_Section::first(array(
                    'id = ?' => $id
        ));

        if (NULL === $section) {
            $view->errorMessage('Section not found');
            self::redirect('/admin/section/');
        }

        $view->set('section', $section)
                ->set('sections', $sections);

        if (RequestMethods::post('submitEditSection')) {
            $this->checkToken();

            $section->parentId = RequestMethods::post('partner', 1);
            $section->title = RequestMethods::post('title');
            $section->rank = RequestMethods::post('rank', 1);
            $section->supportVideo = RequestMethods::post('supportVideo', 0);
            $section->supportPhoto = RequestMethods::post('supportPhoto', 0);
            $section->supportCollection = RequestMethods::post('supportCollection', 0);
            $section->active = RequestMethods::post('active');

            if ($section->validate()) {
                $section->save();

                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/section/');
            } else {
                $view->set('errors', $section->getErrors());
            }
        }
    }

}

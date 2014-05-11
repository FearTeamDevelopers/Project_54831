<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods as RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_Announcement extends Controller
{

    /**
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $announc = App_Model_Announcement::all();

        $view->set('announcements', $announc);
    }

    /**
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('submitAddAnc')) {
            $announc = new App_Model_Announcement(array(
                'title' => RequestMethods::post('title'),
                'body' => RequestMethods::post('text'),
                'signature' => RequestMethods::post('signature'),
                'dateStart' => RequestMethods::post('dateStart'),
                'dateEnd' => RequestMethods::post('dateEnd')
            ));

            if ($announc->validate()) {
                $id = $announc->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Announcement has been successfully created');
                self::redirect('/admin/announcement/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $announc->getErrors())
                        ->set('announc', $announc);
            }
        }
    }

    /**
     * @before _secured, _publisher
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $announc = App_Model_Announcement::first(array(
                    'id = ?' => $id
        ));

        if (NULL === $announc) {
            $view->errorMessage('Announcement not found');
            self::redirect('/admin/announcement/');
        }

        if (RequestMethods::post('submitEditAnc')) {
            $announc->title = RequestMethods::post('title');
            $announc->body = RequestMethods::post('text');
            $announc->signature = RequestMethods::post('signature');
            $announc->active = RequestMethods::post('active');
            $announc->dateStart = RequestMethods::post('datestart');
            $announc->dateEnd = RequestMethods::post('dateend');

            if ($announc->validate()) {
                $announc->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/announcement/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $announc->getErrors());
            }
        }

        $view->set('announcement', $announc);
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $announc = App_Model_Announcement::first(
                        array('id = ?' => $id), array('id')
        );

        if (NULL === $announc) {
            echo 'Announcement not found';
        } else {
            if ($announc->delete()) {
                Event::fire('admin.log', array('success', 'ID: ' . $id));
                echo 'ok';
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                echo 'Unknown error eccured';
            }
        }
    }

}

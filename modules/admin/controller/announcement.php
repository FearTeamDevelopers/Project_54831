<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Announcement extends Controller
{

    /**
     * Action method returns a list of all announcements
     * 
     * @before _secured, _publisher
     */
    public function index()
    {
        $view = $this->getActionView();

        $announc = App_Model_Announcement::all();

        $view->set('announcements', $announc);
    }

    /**
     * Action method used for creating new announcements
     * 
     * @before _secured, _publisher
     */
    public function add()
    {
        $view = $this->getActionView();
        
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddAnc')) {
            if($this->checkCSRFToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/announcement/');
            }
            
            $cache = Registry::get('cache');
            
            $announc = new App_Model_Announcement(array(
                'title' => RequestMethods::post('title'),
                'body' => RequestMethods::post('text'),
                'signature' => RequestMethods::post('signature', 'Marko.in'),
                'dateStart' => RequestMethods::post('datestart', date('Y-m-d', time())),
                'dateEnd' => RequestMethods::post('dateend', date('Y-m-d', time()))
            ));

            if ($announc->validate()) {
                $id = $announc->save();

                Event::fire('admin.log', array('success', 'Announcement id: ' . $id));
                $view->successMessage('Announcement'.self::SUCCESS_MESSAGE_1);
                $cache->invalidate();
                self::redirect('/admin/announcement/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $announc->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('announc', $announc);
            }
        }
    }

    /**
     * Action method used for edititg existing announcements
     * 
     * @param int    $id     announcement id
     * @before _secured, _publisher
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $announc = App_Model_Announcement::first(array(
                    'id = ?' => (int)$id
        ));

        if (NULL === $announc) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/announcement/');
        }
        
        $view->set('announcement', $announc);

        if (RequestMethods::post('submitEditAnc')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/admin/announcement/');
            }
            
            $cache = Registry::get('cache');
            
            $announc->title = RequestMethods::post('title');
            $announc->body = RequestMethods::post('text');
            $announc->signature = RequestMethods::post('signature', 'Marko.in');
            $announc->active = RequestMethods::post('active');
            $announc->dateStart = RequestMethods::post('datestart', date('Y-m-d', time()));
            $announc->dateEnd = RequestMethods::post('dateend', date('Y-m-d', time()));

            if ($announc->validate()) {
                $announc->save();

                Event::fire('admin.log', array('success', 'Announcement id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                $cache->invalidate();
                self::redirect('/admin/announcement/');
            } else {
                Event::fire('admin.log', array('fail', 'Announcement id: ' . $id));
                $view->set('errors', $announc->getErrors());
            }
        }
    }

    /**
     * Method called via ajax used for deleting specific announcement base on 
     * id parameter
     * 
     * @param int    $id     announcement id
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $cache = Registry::get('cache');
            $announc = App_Model_Announcement::first(
                            array('id = ?' => (int) $id), array('id')
            );

            if (NULL === $announc) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($announc->delete()) {
                    Event::fire('admin.log', array('success', 'Announcement id: ' . $id));
                    $cache->invalidate();
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'Announcement id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}

<?php

use Admin\Etc\Controller as Controller;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Request\RequestMethods as RequestMethods;
use THCFrame\Database\Mysqldump as Mysqldump;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_System extends Controller
{

    /**
     * @befor _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $conf = $this->loadConfigFromDb('appstatus');

        if ($conf->value == 1) {
            $status = 'Go Offline';
        } else {
            $status = 'Go Online';
        }

        $view->set('appstatus', $status);
    }

    /**
     * @befor _secured, _admin
     */
    public function clearCache()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('clearCache')) {
            Event::fire('admin.log');
            $cache = Registry::get('cache');
            $cache->clearCache();
            $view->successMessage('Cache has been successfully cleared');
            self::redirect('/admin/system/');
        }
    }

    /**
     * Create and download db bakcup
     * 
     * @befor _secured, _admin
     */
    public function createDatabaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump(array('exclude-tables' => array('tb_user')));

        if (RequestMethods::post('createBackup')) {
            Event::fire('admin.log');
//            if (RequestMethods::post('downloadDump')) {
//                $dump->create()->downloadDump();
//                $view->flashMessage('Database backup has been successfully created');
//                self::redirect('/admin/system/');
//            } else {
            $dump->create();
            $view->successMessage('Database backup has been successfully created');
            self::redirect('/admin/system/');
//            }
        }
    }

    /**
     * @befor _secured, _superadmin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();

        $logQuery = Admin_Model_AdminLog::getQuery(array('tb_adminlog.*'))
                ->join('tb_user', 'tb_adminlog.userId = u.id', 'u', array('u.firstname', 'u.lastname', 'u.role'));

        $log = Admin_Model_AdminLog::initialize($logQuery);

        $view->set('log', $log);
    }

    /**
     * @befor _secured, _admin
     */
    public function archivateNews()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('archivateNews')) {

            $err = false;
            $errCount = 0;

            $count = App_Model_News::count(array(
                        'expirationDate < ?' => date('Y-m-d H:i:s')
            ));

            $expNews = App_Model_News::all(array(
                        'expirationDate < ?' => date('Y-m-d H:i:s')
            ));

            foreach ($expNews as $exp) {
                $archNews = new App_Model_Newsarchive(array(
                    'active' => $exp->getActive(),
                    'urlKey' => $exp->getUrlKey(),
                    'author' => $exp->getAuthor(),
                    'title' => $exp->getTitle(),
                    'shortBody' => $exp->getShortBody(),
                    'body' => $exp->getBody(),
                    'expirationDate' => $exp->getExpirationDate()
                ));

                if ($archNews->validate()) {
                    $archNews->save();
                } else {
                    $err = true;
                    $errCount += 1;
                }

                unset($archNews);
            }

            if ($err) {
                Event::fire('admin.log', array('fail', 'Error count: ' . $errCount));
                $view->errorMessage('An error occured while archiving expired news');
                self::redirect('/admin/system/');
            } else {
                Event::fire('admin.log', array('success', 'Count: ' . $count));
                $view->successMessage('Expired news have been successfully archivated');
                self::redirect('/admin/system/');
            }
        }
    }

    /**
     * @befor _secured, _admin
     */
    public function changeApplicationStatus()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('changeStatus')) {
            $conf = $this->loadConfigFromDb('appstatus');

            if ($conf->value == 2) {
                $value = 1;
            } else {
                $value = 2;
            }
            if ($this->saveConfigToDb('appstatus', $value)) {
                $view->successMessage('Application status have been successfully changed');
                self::redirect('/admin/system/');
            } else {
                $view->errorMessage('An error occured while saving application status');
                self::redirect('/admin/system/');
            }
        }
    }

}

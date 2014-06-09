<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Configuration\Model\Config;

/**
 * 
 */
class Admin_Controller_System extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $status = $this->loadConfigFromDb('appstatus');

        if ($status == 1) {
            $label = 'Go Offline';
        } else {
            $label = 'Go Online';
        }

        $view->set('appstatus', $label);
    }

    /**
     * @before _secured, _admin
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
     * @before _secured, _admin
     */
    public function createDatabaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump(array('exclude-tables' => array('tb_user')));
        $fm = new THCFrame\Filesystem\FileManager();

        if (!is_dir('./temp/db/')) {
            $fm->mkdir('./temp/db/');
        }

        if (RequestMethods::post('createBackup')) {
            Event::fire('admin.log');

            if (RequestMethods::post('downloadDump')) {
                $dump->create()->downloadDump();
                $view->flashMessage('Database backup has been successfully created');
                unset($fm);
                unset($dump);
                self::redirect('/admin/system/');
            } else {
                $dump->create();
                $view->successMessage('Database backup has been successfully created');
                unset($fm);
                unset($dump);
                self::redirect('/admin/system/');
            }
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();

        $logQuery = Admin_Model_AdminLog::getQuery(array('tb_adminlog.*'))
                ->join('tb_user', 'tb_adminlog.userId = u.id', 'u', 
                        array('u.firstname', 'u.lastname', 'u.role'))
                ->order('tb_adminlog.created', 'desc');

        $log = Admin_Model_AdminLog::initialize($logQuery);

        $view->set('log', $log);
    }

    /**
     * @before _secured, _admin
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
     * @before _secured, _admin
     */
    public function changeApplicationStatus()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('changeStatus')) {
            $this->checkToken();
            $status = $this->loadConfigFromDb('appstatus');

            if ($status == 2) {
                $value = 1;
            } else {
                $value = 2;
            }
            if ($this->saveConfigToDb('appstatus', $value)) {
                Event::fire('admin.log', array('success', 'Status: ' . $value));
                $view->successMessage('Application status have been successfully changed');
                self::redirect('/admin/system/');
            } else {
                Event::fire('admin.log', array('fail', 'Status: ' . $value));
                $view->errorMessage('An error occured while saving application status');
                self::redirect('/admin/system/');
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function settings()
    {
        $view = $this->getActionView();
        $config = Config::all();
        $view->set('config', $config);
        
        if(RequestMethods::post('submitEditSet')){
            $this->checkToken();
            $errors = array();
            
            foreach($config as $conf){
                $conf->value = RequestMethods::post($conf->getXkey(), '');
                if($conf->validate()){
                    Event::fire('admin.log', array('success', $conf->getXkey().': ' . $conf->getValue()));
                    $conf->save();
                }else{
                    Event::fire('admin.log', array('fail', $conf->getXkey().': ' . $conf->getValue()));
                    $errors[$conf->xkey] = array_shift($conf->getErrors());
                }
            }

            if(empty($errors)){
                $view->successMessage('Settings have been successfully changed');
                self::redirect('/admin/system/');
            }else{
                $view->set('errors', $errors);
            }
        }
    }

}

<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Configuration\Model\Config;
use THCFrame\Profiler\Profiler;

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
            Event::fire('admin.log', array('success'));
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
        $dump = new Mysqldump(array('exclude-tables-reqex' => array('wp_.*', 'piwik_.*')));
        $fm = new THCFrame\Filesystem\FileManager();

        if (!is_dir(APP_PATH . '/temp/db/')) {
            $fm->mkdir(APP_PATH . '/temp/db/');
        }

        if (RequestMethods::post('createBackup')) {
            Event::fire('admin.log', array('success'));

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
        $log = Admin_Model_AdminLog::all(array(), array('*'), array('created' => 'DESC'));
        $view->set('adminlog', $log);
    }

    /**
     * @before _secured
     */
    public function showProfiler()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        echo Profiler::display();
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
                    'rank' => $exp->getRank(),
                    'expirationDate' => $exp->getExpirationDate(),
                    'rssFeedBody' => $exp->getRssFeedBody()
                ));

                if ($archNews->validate()) {
                    $archNews->save();

                    $exp->delete();
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
                $view->successMessage(self::SUCCESS_MESSAGE_8);
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
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/system/');
            }

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
                $view->errorMessage(self::ERROR_MESSAGE_3);
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

        if (RequestMethods::post('submitEditSet')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/system/');
            }

            $errors = array();

            foreach ($config as $conf) {
                $conf->value = RequestMethods::post($conf->getXkey(), '');
                if ($conf->validate()) {
                    Event::fire('admin.log', array('success', $conf->getXkey() . ': ' . $conf->getValue()));
                    $conf->save();
                } else {
                    Event::fire('admin.log', array('fail', $conf->getXkey() . ': ' . $conf->getValue()));
                    $errors[$conf->xkey] = array_shift($conf->getErrors());
                }
            }

            if (empty($errors)) {
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/system/');
            } else {
                $view->set('errors', $errors);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function generateSitemap()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('generateSitemap')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/system/');
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>'. PHP_EOL
            . '<urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

            $xmlEnd = '</urlset>';

            $host = RequestMethods::server('HTTP_HOST');

            $pageContentXml = "<url><loc>http://{$host}</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/design</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/provas</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/news</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/styling</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/partners</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/contact</loc></url>" . PHP_EOL;

            $news = App_Model_News::all(
                            array('active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s')), array('urlKey'));

            $newsXml = '';
            foreach ($news as $_news) {
                $newsXml .= "<url><loc>http://{$host}/news/detail/{$_news->urlKey}</loc></url>" . PHP_EOL;
            }

            file_put_contents('./sitemap.xml', $xml . $pageContentXml . $newsXml . $xmlEnd);
            
            Event::fire('admin.log', array('success', 'Sitemap.xml generated'));
            $view->successMessage('Sitemap file has been generated');
            self::redirect('/admin/system/');
        }
    }

}

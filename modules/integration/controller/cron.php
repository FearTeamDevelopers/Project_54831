<?php

use Integration\Etc\Controller;
use THCFrame\Database\Mysqldump;
use THCFrame\Filesystem\FileManager;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * Description of Integration_Controller_Cron
 *
 * @author Tomy
 */
class Integration_Controller_Cron extends Controller
{

    /**
     * @before _cron
     */
    public function archivateNews()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        $database = Registry::get('database');
        
        $sql = "INSERT INTO `tb_newsarchive` SELECT * FROM `tb_news` WHERE expirationDate < now()";
        
        Event::fire('cron.log', array('success'));
        $database->execute($sql);
    }

    /**
     * @before _cron
     */
    public function backupDb()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        
        $dump = new Mysqldump(array('exclude-tables-regex' => array('wp_.*', 'piwik_.*')));
        $fm = new FileManager();

        if (!is_dir(APP_PATH . '/temp/db/')) {
            $fm->mkdir(APP_PATH . '/temp/db/');
        }

        Event::fire('cron.log', array('success'));
        $dump->create();
    }

}

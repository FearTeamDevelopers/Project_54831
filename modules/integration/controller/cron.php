<?php

use Integration\Etc\Controller;
use THCFrame\Database\Mysqldump;
use THCFrame\Filesystem\FileManager;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

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
        
    }

    /**
     * @before _cron
     */
    public function backupDb()
    {
        $dump = new Mysqldump(array('exclude-tables' => array('tb_user')));
        $fm = new FileManager();

        if (!is_dir(APP_PATH.'/temp/db/')) {
            $fm->mkdir(APP_PATH.'/temp/db/');
        }

        if (RequestMethods::post('createBackup')) {
            Event::fire('cron.log');
            $dump->create();
        }
    }
}

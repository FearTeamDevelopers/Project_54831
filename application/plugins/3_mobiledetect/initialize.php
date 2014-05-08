<?php

if (!isset($_SESSION['devicetype'])) {
    require_once 'MobileDetect.php';

    $detect = new MobileDetect();
    $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
    
    $_SESSION['devicetype'] = $deviceType;
    
    THCFrame\Events\Events::fire('plugin.mobiledetect.devicetype', array($deviceType));
}
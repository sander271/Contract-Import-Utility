<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/8/2015
 * Time: 4:04 PM
 */
ob_implicit_flush(1);
@ob_end_flush();
for($i=0;$i<10;$i++) {
    echo $i;
    echo str_repeat(" ", 500);
//    ob_flush();
//    flush();
    sleep(1);
}
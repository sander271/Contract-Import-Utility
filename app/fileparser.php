<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 4:28 PM
 */
class FileParser{
    private $file;

    public function __consturct($fileName){
        $file = file($fileName);
    }


}
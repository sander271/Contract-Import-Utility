<?php
session_start();
require_once "../vendor/autoload.php";
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 4:28 PM
 */
class FileParser{
    private $client;
    private $file;
    private $AccountIDs;
    private $startIndex;

    public function __consturct($fileName){
        $this->startIndex = 4;
        $this->file = file("CSV/".$fileName);
        $this->client = $_SESSION['client'];
        $this->AccountIDs = explode(',',$this->file[1]);
    }

    public function getContract(){

    }

    public function numberOfContracts(){
        return 1;
    }

    public function getAccountID(){
//        print_r($this->client);
        return $this->AccountIDs;
    }

    public function getFile(){
        return $this->file;
    }
}
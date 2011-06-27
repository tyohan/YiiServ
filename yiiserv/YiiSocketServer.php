<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YiiSocketServer
 *
 * @author yohan
 */
require_once dirname(__FILE__).'/phpsocketdaemon/socket.php';
require_once dirname(__FILE__).'/phpsocketdaemon/socketServer.php';
class YiiSocketServer extends socketServer
{
    public function accept() {
        $client=parent::accept();
        $client->yiiconfig=$this->yiiconfig;
        $client->yii=$this->yii;
        return $client;
    }
}

?>

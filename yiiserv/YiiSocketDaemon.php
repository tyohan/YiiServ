<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YiiSocketDaemon
 *
 * @author yohan
 */
require_once dirname(__FILE__).'/YiiServ.php';
require_once dirname(__FILE__).'/YiiServClient.php';

class YiiSocketDaemon extends socketDaemon
{
    public function create_server($server_class, $client_class, $yiipath,$configpath,$bind_address = 0, $bind_port = 0) 
    {
        $server=parent::create_server($server_class, $client_class, $bind_address, $bind_port);
        $server->yii=$yiipath;
        $server->yiiconfig=$configpath;
        return $server;
    }
}

?>

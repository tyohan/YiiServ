<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YiiServ
 *
 * @author yohan
 */

require_once dirname(__FILE__).'/YiiSocketServer.php';

class YiiServ extends YiiSocketServer
{
    public $yii;
    public $yiiconfig;
}
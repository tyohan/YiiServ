<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YiiServClient
 *
 * @author yohan
 */

class YiiServClient extends socketServerClient
{
    private $max_total_time = 45;
	private $max_idle_time  = 15;
	private $keep_alive = false;
	private $accepted;
	private $last_action;
        
        public $yiiconfig;
        public $yii;
        
        private function setDefaultParams($request)
        {
            $_SERVER['REQUEST_URI']=$request['url'];
            if($request['method'] === 'post')
            {
                $postString=explode('&', $request['post']);
                foreach ($postString as $key=>$param)
                {
                    $pair = explode('=', $param);
                    $_POST[$pair[0]] = isset($pair[1]) ? $pair[1] : '';
                }
            }
            
            if (strpos($request['url'],'?') !== false) {
				$query = substr($request['url'], strpos($request['url'],'?') + 1);
				$params = explode('&', $query);
				foreach($params as $key => $param) {
					$pair = explode('=', $param);
					$params[$pair[0]] = isset($pair[1]) ? $pair[1] : '';
					unset($params[$key]);
				}
                                $_GET=$params;
				$request['url'] = substr($request['url'], 0, strpos($request['url'], '?'));
			}
           var_dump($_POST);
           $_REQUEST=array_merge($_GET,$_POST);
           $_SERVER['REMOTE_ADDR']=$this->remote_address;
           $_SERVER['REMOTE_PORT']=$this->remote_port;
           $_SERVER['HTTP_HOST']=$request['host'];
            return $request;
        }
        
        protected function get_response($request)
        {
            $response=array();
                        $request=$this->setDefaultParams($request);
                        //handle static file
                        echo "\n";
                        var_dump($request);
                        echo "\n";
			$file = dirname(__FILE__).'/../../..'.$request['url'];
			if (file_exists($file) && is_file($file) && strpos($request['url'], '.php')===FALSE) {
				$header  = "HTTP/{$request['version']} 200 OK\r\n";
				$header .= "Accept-Ranges: bytes\r\n";
				$header .= 'Last-Modified: '.gmdate('D, d M Y H:i:s T', filemtime($file))."\r\n";
				$size    = filesize($file);
				$header .= "Content-Length: $size\r\n";
				$output  = file_get_contents($file);
			}
                         else{
                            require_once($this->yii); 
                            ob_start();
                            $app=Yii::app();
                            if(!isset ($app))
                                $app=Yii::createWebApplication($this->yiiconfig);
                            try
                            {
                                $app->run();
                                $output = ob_get_contents();
                                ob_end_clean();
                                $header  = "HTTP/{$request['version']} 200 OK\r\n";
                                $header .= "Content-Length: ".strlen($output)."\r\n";
                            }
                             catch (CHttpException $e)
                             {
                                switch ($e->statusCode)
                                {
                                case 404:
                                    $errorMessage="Not Found";
                                    break;
                                default:
                                    $errorMessage="Bad Request";
                                    break;
                                }
                                 $header="HTTP/{$request['version']} ".$e->statusCode." {$errorMessage}}\r\n";
                                 $output  = $e->statusCode.": {$errorMessage}";
                                 $header .= "Content-Length: ".strlen($output)."\r\n";
                             }

                            
                        }
                        return array(
                            'output'=>$output,
                            'header'=>$header
                            );
		
        }
	private function handle_request($request)
	{
            
		if (!$request['version'] || ($request['version'] != '1.0' && $request['version'] != '1.1')) {
			// sanity check on HTTP version
			$header  = 'HTTP/'.$request['version']." 400 Bad Request\r\n";
			$output  = '400: Bad request';
			$header .= "Content-Length: ".strlen($output)."\r\n";
		} elseif (!isset($request['method']) || ($request['method'] !== 'get' && $request['method'] !== 'post')) {
			// sanity check on request method (only get and post are allowed)
			$header  = 'HTTP/'.$request['version']." 400 Bad Request\r\n";
			$output  = '400: Bad request';
			$header .= "Content-Length: ".strlen($output)."\r\n";
		} else {
                    //process request and get response
                    $response=$this->get_response($request);
                    $header=$response['header'];
                    $output=$response['output'];
                }
                
                $header .=  'Date: '.gmdate('D, d M Y H:i:s T')."\r\n";
		if ($this->keep_alive) {
			$header .= "Connection: Keep-Alive\r\n";
			$header .= "Keep-Alive: timeout={$this->max_idle_time} max={$this->max_total_time}\r\n";
		} else {
			$this->keep_alive = false;
			$header .= "Connection: Close\r\n";
		}
		return $header."\r\n".$output;
	}

	public function on_read()
	{
		$this->last_action = time();
		if ((strpos($this->read_buffer,"\r\n\r\n")) !== FALSE || (strpos($this->read_buffer,"\n\n")) !== FALSE) {
			$request = array();
			$headers = explode("\n", $this->read_buffer);
			$request['uri'] = $headers[0];
			unset($headers[0]);
			while (list(, $line) = each($headers)) {
				$line = trim($line);
				if ($line != '') {
					$pos  = strpos($line, ':');
					$type = substr($line,0, $pos);
					$val  = trim(substr($line, $pos + 1));
					$request[strtolower($type)] = strtolower($val);
				}
			}
                        
			$uri                = $request['uri'];
			$request['method']  = strtolower(substr($uri, 0, strpos($uri, ' ')));
			$request['version'] = substr($uri, strpos($uri, 'HTTP/') + 5, 3);
			$uri                = substr($uri, strlen($request['method']) + 1);
			$request['url']     = substr($uri, 0, strpos($uri, ' '));
                        if($request['method']==='post')
                        {
                            $request['post']=array_pop($headers);
                        }
			foreach ($request as $type => $val) {
				if ($type == 'connection' && $val == 'keep-alive') {
					$this->keep_alive = true;
				}
			}
			$this->write($this->handle_request($request));
			$this->read_buffer  = '';
		}
	}

	public function on_connect()
	{
		//echo "[httpServerClient] accepted connection from {$this->remote_address}\n";
		$this->accepted    = time();
		$this->last_action = $this->accepted;
	}

	public function on_disconnect()
	{
		//echo "[httpServerClient] {$this->remote_address} disconnected\n";
	}

	public function on_write()
	{
		if (strlen($this->write_buffer) == 0 && !$this->keep_alive) {
			$this->disconnected = true;
			$this->on_disconnect();
			$this->close();
		}
	}

	public function on_timer()
	{
		$idle_time  = time() - $this->last_action;
		$total_time = time() - $this->accepted;
		if ($total_time > $this->max_total_time || $idle_time > $this->max_idle_time) {
			echo "[httpServerClient] Client keep-alive time exceeded ({$this->remote_address})\n";
			$this->close();
		}
	}
}

?>

<?php

namespace FenixEdu\Drive;

use Curl\Curl;

class Client {

    static $instance;

    private $protocol;
    private $host;
    private $curl;
    private $base;
    private $appId;
    private $serviceAppId;
    private $serviceAppSecret;

    public function __construct($host='drive.tecnico.ulisboa.pt', $protocol='https', $appId, $serviceAppId, $serviceAppSecret){
        $this->host = $host;
        $this->protocol = $protocol;
        $this->base = $this->protocol . '://' . $this->host;

        $this->appId = $appId;
        $this->serviceAppId = $serviceAppId;
        $this->serviceAppSecret = $serviceAppSecret;

        $this->curl = new Curl();
        $this->curl->setHeader('X-Requested-With', 'XMLHttpRequest');
        $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        self::$instance = $this;
    }

    public static function getInstance(){
        return self::$instance;
    }

    public function authenticate($istid){
        $this->curl->post($this->base . '/oauth/access_token', array(
            'client_id' =>  $this->serviceAppId,
            'client_secret' => $this->serviceAppSecret,
            'grant_type' => 'client_credentials'
        ));
        
        if($this->curl->error) return $this->error();

        $this->curl->post($this->base . '/api/oauth/provider/' .  $this->appId . '/' . $istid . '?access_token=' . $this->curl->response->access_token);
        if($this->curl->error) return $this->error();
        
        $this->curl->setHeader('Authorization', $this->curl->response->token_type . ' ' . $this->curl->response->access_token);
        return $this->curl->response;
    }

    public function info($dir){
        $this->curl->setHeader('Content-Type', 'application/json;charset=UTF-8');
        $this->curl->get($this->base . '/api/drive/directory/' . $dir);
        if($this->curl->error) return $this->error();
        return $this->curl->response;
    }
    
    public function createDirectory($dir, $name){
        $this->curl->setHeader('Content-Type', 'application/json');
        $this->curl->put($this->base . '/api/drive/directory/' . $dir, array(
            'name' => $name
        ));
        if($this->curl->error) return $this->error();
        return $this->curl->response;
    }

    public function upload($dir, $pathname, $postname){
        $file = new \CURLFile($pathname);
        $file->setPostFilename($postname);
        $this->curl->setHeader('Content-Type', 'multipart/form-data');
        $this->curl->post($this->base . '/api/drive/directory/' . $dir, array(
            'file' => $file
        ));
        if($this->curl->error) return $this->error();
        return $this->curl->response;
    }

    public function download($file, $to){
        $curl = $this->fetch($file);
        if($curl instanceof \Error) return $curl;
        foreach(explode(';', $curl->responseHeaders['Content-Disposition']) as $header){
            if(strpos($header, 'filename') !== FALSE){
                $header = str_replace('filename=', '', $header);
                $header = str_replace('"', '', $header);
                $header = str_replace("'", '', $header);
                $filename = trim($header);
                $filepath = rtrim($to, '/') . '/' . $filename;
                break;
            }
        }
        file_put_contents($filepath, $curl->response);
        return $filepath;
    }

    public function fetch($file){
        $curl = $this->curl;
        $curl->get($this->base . '/api/drive/file/' . $file . '/download');
        if($curl->httpStatusCode >= 300 &&  $curl->httpStatusCode <= 307 && $curl->responseHeaders['Location']){
            $url = $curl->responseHeaders['Location'];
            $curl = new Curl();
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->get($url);
            
        }
        if($curl->error) return $this->error($curl);
        return $curl;
    }

    public function error($curl = false){
        if(!$curl) $curl = $this->curl;
        return new \Error($curl->httpErrorMessage, $curl->httpError);
    }
}
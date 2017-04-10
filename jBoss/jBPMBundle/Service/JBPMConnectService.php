<?php

namespace jBoss\jBPMBundle\Service;

use GuzzleHttp\Client as Client;

class JBPMConnectService
{
	/**
     * @var \GuzzleHttp\Client
     */
    protected $client;
    
    protected $baseUrl;
    protected $username;
    protected $password;

    protected $defaultOptions;
    protected $options;
	/**
     * class constructor
     * 
     * @param array $config an array of configuration values
     */
    public function __construct( $config)
    {
        $this->baseUrl = $config['baseUrl'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->client = new Client();
        $this->defaultOptions = [
            'auth' => [$this->username, $this->password],
            //'headers' => ['Accept'=>'application/json'],
            'connect_timeout' => '10.00',
            'timeout' => '10.00',
            'verify' => false
        ];
    }

    /**
     * @param $action string, $method request method( GET | POST ), $params array
     * 
     * @return responce array
     */  
    public function callJbpm($action, $method, $params)
    {
        $this->options = array_merge($params,$this->defaultOptions);
        try {
            $responce = $this->client->request($method, $this->baseUrl.$action, $this->options);
            $code = $responce->getStatusCode(); // 200
            $reason = $responce->getReasonPhrase(); // OK
            $ressultDataXml = simplexml_load_string($responce->getBody()->getContents(),'SimpleXMLElement',LIBXML_NOCDATA); // OK
            $ressultDataJson = json_encode($ressultDataXml);
            $ressultData = json_decode($ressultDataJson,TRUE);

            return [
                'code' => $code,
                'reason' => $reason,
                'body' => $ressultData,
                'error' => [
                    'message' => ''
                ]
            ];
        } catch (RequestException $e) {
            /*echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                echo Psr7\str($e->getResponse());
            }*/
            return [
                'code' => 0,
                'reason' => '',
                'body' => [],
                'error' => [
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}
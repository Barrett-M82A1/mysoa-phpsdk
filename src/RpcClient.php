<?php
/**
 * Php Rpc Client
 */

namespace Mysoa;

use Mysoa\{Request,Dispatcher};

class RpcClient
{
    /**
     * 服务名称
     * @var string
     */
    private $serviceName;

    /**
     * 服务信息
     * @var array
     */
    private static $serivceInfo;

    /**
     * RPC访问对象
     * @var Object
     */
    private static $service;

    /**
     * 配置信息
     * @var array
     */
    private static $config;

    /**
     * RpcClient constructor.
     * @param string $serviceName 服务名称
     * @param array  $mysoa       服务中心配置信息
     */
    public function __construct($serviceName,$config)
    {

        $this->serviceName = $serviceName;

        //是否设置过配置信息
        if (!isset(self::$config)) {
            self::$config = array_merge(self::$config, $config);
        }

        //已经存在则不进行处理
        if (isset(self::$services[$serviceName])) return $this;

        //检测本地是否存在RPC服务
        $Dispatcher = new Dispatcher;
        $check = $Dispatcher->localService($serviceName,self::$config['config_path']);

        //设置RPC访问对象
        if ($check){
            //设置服务信息
            self::$serivceInfo[$serviceName] = $check['data'];

            //设置TPC对象
            $client = new \swoole_client(SWOOLE_SOCK_TCP);
            $client->connect(self::$serivceInfo['ip'],self::$serivceInfo['port'], 0.5);

            self::$service[$serviceName] = $client;
        }else{
            //未存在服务
            self::$service[$serviceName] = NULL;
        }
    }

    /**
     * 请求Mysoa获取对应服务
     */
    public static function queryMysoa(){

        //获取服务中心连接信息
        $Dispatcher = new Dispatcher;
        $mysoa = $Dispatcher->choiceMysoa(self::$config['mysoa']);
        if(!$mysoa['status']){
            return ['status'=>false,'msg'=>'未配置服务中心信息!','data'=>''];
        }

        $client = new swoole_client(SWOOLE_SOCK_TCP);

        //获取服务
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        if (!$client->connect($config['mysoa'][0]['ip'],$config['mysoa'][0]['port'], 0.5)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }

        //设置注册信息
        $msg = json_encode(array_merge($config['service'],['method'=>'register']));
        $str = pack('N', strlen($msg)) . $msg;

        //提交注册
        $client->send($this->pack($data));
        echo $client->recv();
        $client->close();
    }

    /**
     * 请求数据序列化
     * @param $request
     * @return string
     */
    public function pack($request){
        $msg = json_encode($request,true);
        return pack('N', strlen($msg)) . $msg;
    }

    /**
     * 接收数据反序列化
     * @param $data
     */
    public function unpack($data){

    }

    public function __call($name, $arguments)
    {
        $client = self::$services[$this->serviceName];
        $request = new Request();
        $request->setService($this->serviceName);
        $request->setAction($name);
        $request->setParameters($arguments);

        $client->send($this->pack($request));

        $reponse = $client->recv();

        $body = substr($reponse, 4);
        return json_decode($body,true);
    }

}


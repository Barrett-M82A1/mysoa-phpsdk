<?php
/**
 * Php Rpc Client
 */
namespace mysoa;

use mysoa\{Request,Dispatcher};

class RpcClient
{
    /**
     * 服务名称
     * @var string
     */
    private static $serviceName;

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
     * RpcClient constructor.
     * @param string $serviceName 服务名称
     */
    public function __construct($serviceName)
    {
        self::$serviceName = $serviceName;

        // 设置TCP对象
        self::setTcp();
    }

    /**
     * 设置TCP对象
     */
    public static function setTcp()
    {
        $Dispatcher = new Dispatcher(self::$serviceName);
        $loadService = $Dispatcher->loadService(self::$serviceName);

        // 设置服务信息
        self::$serivceInfo[self::$serviceName] = $loadService['data'];

        // 根据权重随机选择服务
        $service = $Dispatcher->weight(self::$serivceInfo[self::$serviceName]);

        // 设置TCP对象
        if ($service['status']){
            $client = new \swoole_client(SWOOLE_SOCK_TCP);
            $client->connect($service['data']['ip'],$service['data']['port'], 0.5);

            self::$service[self::$serviceName] = $client;
        }else{
            self::$service[self::$serviceName] = false;
        }
    }

    /**
     * 订阅服务
     * @param array $config 配置参数
     */
    public static function queryMysoa($config = [])
    {
        // 随机选择服务中心
        $key = array_rand($config['mysoa']);

        // 获取服务
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        if (!$client->connect($config['mysoa'][$key]['ip'],$config['mysoa'][$key]['port'], 0.5)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }

        // 设置注册信息
        $data = [
            'app_name'      =>  $config['app_name'],
            'service'       =>  $config['service'],
            'ip'            =>  $config['ip'],
            'method'        =>  'subscribe',
            'port'          =>  $config['port'],
            'notify_port'   =>  $config['notify_port']
        ];

        // 提交注册
        $client->send(self::pack($data));

        // 关闭连接
        $client->close();
    }

    /**
     * 重置服务信息
     * @param string $data   服务配置信息
     * @param boole  $setTcp 是否重置TCP对象
     */
    public static function rest($data,$setTcp = false){
        // 更新服务信息
        Dispatcher::configUpdate(self::unpack($data));

        // 重置服务信息
        Dispatcher::reset();

        if ($setTcp){
            // 设置TCP对象
            self::setTcp();
        }
    }

    /**
     * 请求数据序列化
     * @param $request
     * @return string
     */
    public static function pack($request){
        $msg = json_encode($request);
        return pack('N', strlen($msg)) . $msg;
    }

    /**
     * 接收数据反序列化
     * @param $data
     */
    public static function unpack($data){
        return json_decode(substr($data, 4),true);
    }

    /**
     * RPC执行结果回调
     * @param string $method 要操作的方法
     * @param array  $param  业务参数
     * @return array|mixed
     */
    public function __call($method,$param)
    {
        if (!self::$service[self::$serviceName]){
            return ['status'=>false,'msg'=>'Remote service does not exist','data'=>''];
        }

        // 设置对象实例
        $Client = self::$service[self::$serviceName];

        $Request = new Request();

        // 设置请求信息
        $data = [
            'service'   =>  self::$serviceName,
            'method'    =>  $method,
            'param'     =>  json_encode($param)
        ];
        $msg = json_encode($data);

        // 服务名称
        $Request->setParam($data);

        $Client->send(pack('N', strlen($msg)) . $msg);

        $response = $Client->recv();

        return self::unpack($response);
    }
}
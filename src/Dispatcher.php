<?php
/**
 * Service Dispatcher
 */

namespace mysoa;

class Dispatcher{

    /**
     * 服务配置信息
     * @var array
     */
    private static $config;

    /**
     * Dispatcher constructor.
     * @param $serviceName 服务名称
     */
    public function __construct($serviceName)
    {
        // 是否需要载入配置信息
        if (!isset(self::$config[$serviceName])) {
            $config = include __DIR__."/../../../../config/config.php";

            // 查询服务信息
            foreach ($config['rpc'] as $key => $item){
                if($item['name'] === $serviceName){
                    $service[$key] = $item;
                }
            }

            // 设置服务信息
            self::$config[$serviceName] = $service;
        }
    }

    /**
     * 加载配置文件服务信息
     * @param string $service 服务名称
     * @return array
     */
    public static function loadService($service)
    {
        // 检测本地服务信息是否存在
        if (!isset(self::$config[$service])) {
            return ['status' => false, 'msg' => 'The local service does not exist', 'data' => []];
        }

        return ['status'=>true,'msg'=>'Successful local service','data'=>self::$config[$service]];
    }

    /**
     * 更新本地配置
     * @param array $service 服务列表
     */
    public static function configUpdate($service){
        $config = include __DIR__."/../../../../config/config.php";
        $config['rpc'] = $service;
        $fp= fopen(__DIR__."/../../../../config/config.php", "w");
        fwrite($fp,"<?php\nreturn ".var_export($config,true).";");
        fclose($fp);
    }

    /**
     * 权重随机选择算法
     * @param array $service 服务列表
     * @return array
     */
    public function weight($service)
    {
        if (count($service) === 0){
            return ['status'=>false,'msg'=>'Array does not exist','data'=>''];
        }

        $weight = 0;
        $tempdata = [];
        foreach ($service as $key) {
            $weight += $key['weight'];
            for ($i = 0; $i < $key['weight']; $i ++) {
                $tempdata[] = $key;
            }
        }
        $use = rand(0, $weight - 1);
        return ['status'=>true,'msg'=>'Successful screening','data'=>$tempdata[$use]];
    }

    /**
     * 重置配置参数
     */
    public static function reset()
    {
        self::$config = [];
    }
}
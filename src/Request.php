<?php
/**
 * Consumer Request
 */

namespace mysoa;

class Request
{
    /**
     * 请求数据
     * @var array
     */
    protected $param = [
        // 唯一ID
        'requestId' =>  '',

        // RPC身份令牌
        //'token'     =>  '',

        // 服务名称
        'service'   =>  '',

        // 请求方法
        'method'    =>  '',

        // 请求参数
        'param'     =>  '',
    ];

    /**
     * Request constructor.
     * @param string $requestId 针对链式调用，继承上游 requestId
     */
    public function __construct()
    {
        if (!$this->param['requestId']) {
            $this->createRequestId();
        }
    }

    /**
     * 设置请求参数
     * @param array $param
     */
    public function setParam($param)
    {
        $this->param = array_merge($this->param,$param);
    }

    /**
     * 获取请求参数
     */
    public function getParam(){
        return $this->param;
    }

    /**
     * 创建RequestID
     */
    private function createRequestId()
    {
        $this->param['requestId'] = md5(uniqid(mt_rand(1, 1000000), true));
    }
}
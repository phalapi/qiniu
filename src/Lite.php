<?php
namespace PhalApi\Qiniu;

/**
 * 七牛接口调用
 * 1、图片文件上传
 *
 * 参考：http://developer.qiniu.com/docs/v6/sdk/php-sdk.html
 *
 * @author: dogstar 2015-03-17
 */

class Lite {

    protected $config;

    protected $client;

    protected $preffix;

    /**
     * @param string $config['accessKey']  统一的key
     * @param string $config['secretKey']
     * @param string $config['space_bucket']  自定义配置的空间
     * @param string $config['space_host']  
     */
    public function __construct($config = NULL) {
        $this->config = $config;

        if ($this->config === NULL) {
            $this->config = \PhalApi\DI()->config->get('app.Qiniu');
        }

        $this->client = \Qiniu\Qiniu::create(array(
            'access_key' => $this->config['access_key'],
            'secret_key' => $this->config['secret_key'],
            'bucket'     => $this->config['space_bucket'],
        ));

        if (!empty($config['preffix'])) {
            $this->preffix = $config['preffix'];
        }
    }

    public function getClient() {
        return $this->client;
    }

    /**
     * 文件上传
     * @param string $filePath 待上传文件的绝对路径
     * @return string 上传成功后的URL，失败时返回空
     */
    public function uploadFile($filePath, $fileExt = '')
    {
        $fileUrl = '';

        if (!file_exists($filePath)) {
            return $fileUrl;
        }

        $fileExt = !empty($fileExt) ? '.' . ltrim($fileExt, '.') : ''; // 支持扩展名

        $fileName = $this->preffix
            . date('YmdHis_', $_SERVER['REQUEST_TIME'])
            . md5(\PhalApi\Tool::createRandStr(8) . microtime(true)) . $fileExt;

        $res = $this->client->uploadFile($filePath, $fileName);

        if (!is_object($res) || empty($res->data) || empty($res->data['url'])) {
            \PhalApi\DI()->logger->debug('failed to upload file to qiniu', $res);
        } else {
            $fileUrl = empty($this->config['space_host'])
                ? $res->data['url'] : rtrim($this->config['space_host'], '/') . '/' . $fileName;
            \PhalApi\DI()->logger->debug('succeed to upload file to qiniu', $res->data);
        }

        return $fileUrl;
    }
	
    public function delete($key) {
        $res = $this->client->delete($key);
        return $res;
    }
}

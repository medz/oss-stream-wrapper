<?php

namespace Medz\Component\StreamWrapper\AliyunOSS;

use OSS\OssClient;

/**
 * Aliyun OSS .
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class AliyunOSS extends OssClient
{
    protected static $_wrapperClients = [];

    protected static $bucket;

    public static function getBucket()
    {
        return static::$bucket;
    }

    public function setBucket(string $bucket)
    {
        static::$bucket = $bucket;

        return $this;
    }

    /**
     * Register this object as stream wrapper client.
     *
     * @param string $name
     *
     * @return oss
     */
    public function registerAsClient($name)
    {
        self::$_wrapperClients[$name] = $this;

        return $this;
    }

    /**
     * Unregister this object as stream wrapper client.
     *
     * @param string $name
     *
     * @return oss
     */
    public function unregisterAsClient($name)
    {
        unset(self::$_wrapperClients[$name]);

        return $this;
    }

    /**
     * Get wrapper client for stream type.
     *
     * @param string $name
     *
     * @return oss
     */
    public static function getWrapperClient($name)
    {
        return self::$_wrapperClients[$name];
    }

    /**
     * Register this object as stream wrapper.
     *
     * @param string $name
     *
     * @return oss
     */
    public function registerStreamWrapper($name = 'oss')
    {
        stream_register_wrapper($name, 'Medz\\Component\\AliyunOSS\\AliyunOssStream');
        $this->registerAsClient($name);
    }

    /**
     * Unregister this object as stream wrapper.
     *
     * @param string $name
     *
     * @return oss
     */
    public function unregisterStreamWrapper($name = 'oss')
    {
        stream_wrapper_unregister($name);
        $this->unregisterAsClient($name);
    }
} // END class AliyunOSS extends OssClient

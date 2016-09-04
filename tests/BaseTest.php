<?php

namespace Medz\Component\StreamWrapper\AliyunOSS\Tests;

use PHPUnit_Framework_TestCase;
use Medz\Component\StreamWrapper\AliyunOSS\AliyunOSS;

/**
 * 基础测试
 *
 * @author Seven Du <lovevipdsw@outlook.com> 
 **/
class BaseTest extends PHPUnit_Framework_TestCase
{
    protected $oss;

    protected $bucketName;

    // 初始化
    public function setUp()
    {
        $this->bucketName = getenv('OSS_BUCKET');
        $this->oss = new AliyunOSS(
            getenv('OSS_ACCESS_KEY_ID'),
            getenv('OSS_ACCESS_KEY_SECRET'),
            getenv('OSS_ENDPOINT')
        );
        $this->oss->setBucket($this->bucketName);
        $this->oss->registerStreamWrapper('oss');
    }

    // 删除协议
    public function tearDown()
    {
        $this->oss->unregisterStreamWrapper('oss');
    }

    /**
     * Test get client.
     *
     * @return void
     * @author Seven Du <lovevipdsw@outlook.com>
     * @homepage http://medz.cn
     */
    public function testGetClient()
    {
        $this->assertTrue($this->oss->getWrapperClient('oss') instanceof AliyunOSS);
    }

    /**
     * Test put file to OSS.
     *
     * @return void
     * @author Seven Du <lovevipdsw@outlook.com>
     * @homepage http://medz.cn
     */
    public function testPutFile()
    {
        $data = 'medz';
        $dataLength = file_put_contents('oss://oss-stream-wrapper/phpunit.txt', $data);
        $this->assertSame(strlen($data), $dataLength);
    }

    /**
     * Test file exists.
     *
     * @return void
     * @author Seven Du <lovevipdsw@outlook.com>
     * @homepage http://medz.cn
     */
    public function testFileExists()
    {
        if (!file_exists('oss://oss-stream-wrapper/phpunit.txt')) {
            file_put_contents('oss://oss-stream-wrapper/phpunit.txt', 'medz');
        }

        $this->assertFileExists('oss://oss-stream-wrapper/phpunit.txt');
    }

    /**
     * Test get file content by oss.
     *
     * @return void
     * @author Seven Du <lovevipdsw@outlook.com>
     * @homepage http://medz.cn
     */
    public function testGetFileContent()
    {
        $data = file_get_contents('oss://oss-stream-wrapper/phpunit.txt');
        $this->assertSame($data, 'medz');
    }

} // END class BaseTest extends PHPUnit_Framework_TestCase

<?php

namespace Medz\Component\StreamWrapper\AliyunOSS;

use Exception;
use OSS\OssClient;
use Medz\Component\WrapperInterface\WrapperInterface;

/**
 * 阿里云OSS Streams.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class AliyunOssStream implements WrapperInterface
{
    /**
     * @var bool Write the buffer on fflush()?
     */
    private $_writeBuffer = false;
    /**
     * @var int Current read/write position
     */
    private $_position = 0;
    /**
     * @var int Total size of the object as returned by oss (Content-length)
     */
    private $_objectSize = 0;
    /**
     * @var string File name to interact with
     */
    private $_objectName = null;
    /**
     * @var string Current read/write buffer
     */
    private $_objectBuffer = null;
    /**
     * @var array Available buckets
     */
    private $_bucketList = [];
    /**
     * @var oss
     */
    private $_oss = null;

    /**
     * Retrieve client for this stream type.
     *
     * @param string $path
     *
     * @return oss
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @homepage http://medz.cn
     */
    protected function _getOssClient($path)
    {
        if ($this->_oss === null) {
            $url = explode(':', $path);

            if (!$url) {
                throw new Exception("Unable to parse URL $path");
            }

            $this->_oss = AliyunOSS::getWrapperClient($url[0]);

            if (!$this->_oss) {
                throw new Exception("Unknown client for wrapper {$url[0]}");
            }
        }

        return $this->_oss;
    }

    /**
     * Extract object name from URL.
     *
     * @param string $path
     *
     * @return string
     */
    protected function _getNamePart($path)
    {
        $url = parse_url($path);
        if ($url['host']) {
            return !empty($url['path']) ? $url['host'].$url['path'] : $url['host'];
        }

        return '';
    }

    /**
     * Open the stream.
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     *
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $name = $this->_getNamePart($path);
        // If we open the file for writing, just return true. Create the object
        // on fflush call
        if (strpbrk($mode, 'wax')) {
            $this->_objectName = $name;
            $this->_objectBuffer = null;
            $this->_objectSize = 0;
            $this->_position = 0;
            $this->_writeBuffer = true;
            $this->_getOssClient($path);

            return true;
        } else {
            // Otherwise, just see if the file exists or not
            try {
                $info = $this->_getOssClient($path)->getObjectMeta(AliyunOSS::getBucket(), $name);
                if ($info) {
                    $this->_objectName = $name;
                    $this->_objectBuffer = null;
                    $this->_objectSize = (int) $info['content-length'];
                    $this->_position = 0;
                    $this->_writeBuffer = false;
                    // $this->_getOssClient($path);
                    return true;
                }
            } catch (\OSS\Core\OssException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Close the stream.
     *
     * @return void
     */
    public function stream_close()
    {
        $this->_objectName = null;
        $this->_objectBuffer = null;
        $this->_objectSize = 0;
        $this->_position = 0;
        $this->_writeBuffer = false;
        unset($this->_oss);
    }

    /**
     * Read from the stream.
     *
     * http://bugs.php.net/21641 - stream_read() is always passed PHP's
     * internal read buffer size (8192) no matter what is passed as $count
     * parameter to fread().
     *
     * @param int $count
     *
     * @return string
     */
    public function stream_read($count)
    {
        if (!$this->_objectName) {
            return false;
        }

        // make sure that count doesn't exceed object size
        if ($count + $this->_position > $this->_objectSize) {
            $count = $this->_objectSize - $this->_position;
        }

        $range_start = $this->_position;
        $range_end = $this->_position + $count;

        // Only fetch more data from OSS if we haven't fetched any data yet (postion=0)
        // OR, the range end position is greater than the size of the current object
        // buffer AND if the range end position is less than or equal to the object's
        // size returned by OSS
        if (($this->_position == 0) || (($range_end > strlen($this->_objectBuffer)) && ($range_end <= $this->_objectSize))) {
            $options = [
                OssClient::OSS_RANGE => $range_start.'-'.$range_end,
            ];
            $this->_objectBuffer .= $this->_oss->getObject(AliyunOSS::getBucket(), $this->_objectName, $options);
        }

        $data = substr($this->_objectBuffer, $this->_position, $count);
        $this->_position += strlen($data);

        return $data;
    }

    /**
     * Write to the stream.
     *
     * @param string $data
     *
     * @return int
     */
    public function stream_write($data)
    {
        if (!$this->_objectName) {
            return 0;
        }
        $len = strlen($data);
        $this->_objectBuffer .= $data;
        $this->_objectSize += $len;
        // TODO: handle current position for writing!
        return $len;
    }

    /**
     * End of the stream?
     *
     * @return bool
     */
    public function stream_eof()
    {
        if (!$this->_objectName) {
            return true;
        }

        return $this->_position >= $this->_objectSize;
    }

    /**
     * What is the current read/write position of the stream.
     *
     * @return int
     */
    public function stream_tell()
    {
        return $this->_position;
    }

    public function stream_lock($operation)
    {
        return false;
    }

    /**
     * Enter description here...
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     *
     * @return bool
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    /**
     * Enter description here...
     *
     * @param int $cast_as
     *
     * @return resource
     */
    public function stream_cast($cast_as)
    {
    }

    /**
     * Update the read/write position of the stream.
     *
     * @param int $offset
     * @param int $whence
     *
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if (!$this->_objectName) {
            return false;
        }

        switch ($whence) {
            case SEEK_CUR:
                // Set position to current location plus $offset
                $new_pos = $this->_position + $offset;
                break;
            case SEEK_END:
                // Set position to end-of-file plus $offset
                $new_pos = $this->_objectSize + $offset;
                break;
            case SEEK_SET:
            default:
                // Set position equal to $offset
                $new_pos = $offset;
                break;
        }

        $ret = ($new_pos >= 0 && $new_pos <= $this->_objectSize);
        if ($ret) {
            $this->_position = $new_pos;
        }

        return $ret;
    }

    /**
     * Flush current cached stream data to storage.
     *
     * @return bool
     */
    public function stream_flush()
    {
        // If the stream wasn't opened for writing, just return false
        if (!$this->_writeBuffer) {
            return false;
        }
        $ret = $this->_oss->putObject(AliyunOSS::getBucket(), $this->_objectName, $this->_objectBuffer);
        $this->_objectBuffer = null;

        return $ret;
    }

    /**
     * Returns data array of stream variables.
     *
     * @return array
     */
    public function stream_stat()
    {
        if (!$this->_objectName) {
            return false;
        }

        $stat = [];
        $stat['dev'] = 0;
        $stat['ino'] = 0;
        $stat['mode'] = 0777;
        $stat['nlink'] = 0;
        $stat['uid'] = 0;
        $stat['gid'] = 0;
        $stat['rdev'] = 0;
        $stat['size'] = 0;
        $stat['atime'] = 0;
        $stat['mtime'] = 0;
        $stat['ctime'] = 0;
        $stat['blksize'] = 0;
        $stat['blocks'] = 0;

        if (($slash = strstr($this->_objectName, '/')) === false || $slash == strlen($this->_objectName) - 1) {
            /* bucket */
            $stat['mode'] |= 040000;
        } else {
            $stat['mode'] |= 0100000;
        }
        $info = $this->_oss->getObjectMeta(AliyunOSS::getBucket(), $this->_objectName);
        $info = $info['_info'];
        if (!empty($info['_info'])) {
            $stat['size'] = $info['download_content_length'];
            $stat['atime'] = time();
            $stat['mtime'] = $info['filetime'];
        }

        return $stat;
    }

    /**
     * Attempt to delete the item.
     *
     * @param string $path
     *
     * @return bool
     */
    public function unlink($path)
    {
        return $this->_getOssClient($path)->deleteObject(AliyunOSS::getBucket(), $this->_getNamePart($path));
    }

    /**
     * Attempt to rename the item.
     *
     * @param string $path_from
     * @param string $path_to
     *
     * @return bool False
     */
    public function rename($path_from, $path_to)
    {
        // TODO: Renaming isn't supported, always return false
        return false;
    }

    /**
     * Create a new directory.
     *
     * @param string $path
     * @param int    $mode
     * @param int    $options
     *
     * @return bool
     */
    public function mkdir($path, $mode, $options)
    {
        return $this
            ->_getOssClient($path)
            ->createObjectDir(AliyunOSS::getBucket(), $this->_getNamePart($path));
    }

    /**
     * Remove a directory.
     *
     * @param string $path
     * @param int    $options
     *
     * @return bool
     */
    public function rmdir($path, $options)
    {
        return false;
    }

    /**
     * Return the next filename in the directory.
     *
     * @return string
     */
    public function dir_readdir()
    {
        $object = current($this->_bucketList);
        if ($object !== false) {
            next($this->_bucketList);
        }

        return $object;
    }

    /**
     * Attempt to open a directory.
     *
     * @param string $path
     * @param int    $options
     *
     * @return bool
     */
    public function dir_opendir($path, $options)
    {
        $dirName = $this->_getNamePart($path).'/';
        if (preg_match('@^([a-z0-9+.]|-)+://$@', $path) || $dirName == '/') {
            $list = $this->_getOssClient($path)->listObjects(AliyunOSS::getBucket());
        } else {
            $list = $this
                ->_getOssClient($path)
                ->listObjects(AliyunOSS::getBucket(), [
                    OssClient::OSS_PREFIX => $dirName,
                ]);
        }

        foreach ((array) $list->getPrefixList() as $l) {
            array_push($this->_bucketList, basename($l->getPrefix()));
        }

        foreach ((array) $list->getObjectList() as $l) {
            if ($l == $dirName) {
                continue;
            }

            array_push($this->_bucketList, basename($l->getKey()));
        }

        return $this->_bucketList !== false;
    }

    /**
     * Return array of URL variables.
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array
     */
    public function url_stat($path, $flags)
    {
        $stat = [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 0777,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0,
        ];
        $name = $this->_getNamePart($path);

        try {
            $info = $this->_getOssClient($path)->getObjectMeta(AliyunOSS::getBucket(), $name);
            if (isset($info['_info']) && !empty($info['_info'])) {
                $info = $info['_info'];
                $stat['size'] = $info['download_content_length'];
                $stat['atime'] = time();
                $stat['mtime'] = $info['filetime'];
                $stat['mode'] |= 0100000;
            }
        } catch (\OSS\Core\OssException $e) {
            $stat['mode'] |= 040000;
        }

        return $stat;
    }

    /**
     * Reset the directory pointer.
     *
     * @return bool True
     */
    public function dir_rewinddir()
    {
        reset($this->_bucketList);

        return true;
    }

    /**
     * Close a directory.
     *
     * @return bool True
     */
    public function dir_closedir()
    {
        $this->_bucketList = [];

        return true;
    }
} // END class AliyunOssStream implements WrapperInterface

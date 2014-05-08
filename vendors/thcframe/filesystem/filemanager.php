<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base as Base;
use THCFrame\Filesystem\Exception as Exception;

class FileManager extends Base
{

    /**
     * @readwrite
     */
    protected $_path;
    
    protected static $filesExtensions = array('rtf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'zip', 'rar');

    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    /**
     * 
     * @param type $files
     * @return \ArrayObject
     */
    private function toIterator($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        return $files;
    }

    /**
     * 
     * @param type $originFile
     * @param type $targetFile
     * @param type $override
     * @throws IOException
     */
    public function copy($originFile, $targetFile, $override = false)
    {
        if (stream_is_local($originFile) && !is_file($originFile)) {
            throw new Exception\IO(sprintf('Failed to copy %s because file not exists', $originFile));
        }

        $this->mkdir(dirname($targetFile));

        if (!$override && is_file($targetFile)) {
            $doCopy = filemtime($originFile) > filemtime($targetFile);
        } else {
            $doCopy = true;
        }

        if ($doCopy) {
            $source = fopen($originFile, 'r');
            $target = fopen($targetFile, 'w+');
            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);
            unset($source, $target);

            if (!is_file($targetFile)) {
                throw new Exception\IO(sprintf('Failed to copy %s to %s', $originFile, $targetFile));
            }
        }
    }

    /**
     * 
     * @param type $files
     * @throws IOException
     */
    public function remove($files)
    {
        $files = iterator_to_array($this->toIterator($files));
        $files = array_reverse($files);
        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                continue;
            }

            if (is_dir($file) && !is_link($file)) {
                $this->remove(new \FilesystemIterator($file));

                if (true !== @rmdir($file)) {
                    throw new Exception\IO(sprintf('Failed to remove directory %s', $file));
                }
            } else {
                if (is_dir($file)) {
                    if (true !== @rmdir($file)) {
                        throw new Exception\IO(sprintf('Failed to remove file %s', $file));
                    }
                } else {
                    if (true !== @unlink($file)) {
                        throw new Exception\IO(sprintf('Failed to remove file %s', $file));
                    }
                }
            }
        }
    }

    /**
     * 
     * @param type $origin
     * @param type $target
     * @param type $overwrite
     * @throws IOException
     */
    public function rename($origin, $target, $overwrite = false)
    {
        if (!$overwrite && is_readable($target)) {
            throw new Exception\IO(sprintf('Cannot rename because the target "%s" already exist.', $target));
        }

        if (true !== @rename($origin, $target)) {
            throw new Exception\IO(sprintf('Cannot rename "%s" to "%s".', $origin, $target));
        }
    }

    /**
     * 
     * @param type $dirs
     * @param type $mode
     * @throws IOException
     */
    public function mkdir($dirs, $mode = 0777)
    {
        foreach ($this->toIterator($dirs) as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            if (true !== @mkdir($dir, $mode, true)) {
                throw new Exception\IO(sprintf('Failed to create %s', $dir));
            }
        }
    }

    /**
     * 
     * @param type $files
     * @param type $mode
     * @param type $umask
     * @param type $recursive
     * @throws IOException
     */
    public function chmod($files, $mode, $umask = 0000, $recursive = false)
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && is_dir($file) && !is_link($file)) {
                $this->chmod(new \FilesystemIterator($file), $mode, $umask, true);
            }
            if (true !== @chmod($file, $mode & ~$umask)) {
                throw new Exception\IO(sprintf('Failed to chmod file %s', $file));
            }
        }
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getExtension($path)
    {
        if ($path != '') {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            return $ext;
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getFileSize($path)
    {
        if ($path != '') {
            $size = filesize($path);
            return $size;
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getFileName($path)
    {
        if ($path != '') {
            $name = pathinfo($path, PATHINFO_FILENAME);
            return $name;
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $filename
     * @param type $content
     * @param type $mode
     * @throws IOException
     */
    public function dumpFile($filename, $content, $mode = 0666)
    {
        $dir = dirname($filename);

        if (!is_dir($dir)) {
            $this->mkdir($dir);
        } elseif (!is_writable($dir)) {
            throw new Exception\IO(sprintf('Unable to write in the %s directory\n', $dir));
        }

        $tmpFile = tempnam($dir, basename($filename));

        if (false === @file_put_contents($tmpFile, $content)) {
            throw new Exception\IO(sprintf('Failed to write file "%s".', $filename));
        }

        $this->rename($tmpFile, $filename, true);
        $this->chmod($filename, $mode);
    }

}

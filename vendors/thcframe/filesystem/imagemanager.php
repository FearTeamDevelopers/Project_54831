<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base as Base;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Filesystem\Exception as Exception;
use THCFrame\Core\StringMethods as StringMethods;

/**
 * Usage example
 * 
 * $image = new ImageManager(); 
 * $image->upload('photo'); //upload from $_FILES['photo']
 * $image->resizeToWidth(150);
 * $image->save(true); //save as thumbnail
 * 
 * $image = new ImageManager(); 
 * $image->load(path_to_image); //load image from /public/uploads/images/....
 * $image->resizeToHeight(300);
 * $image->save(); //save image, do not create thumbnail
 */

/**
 * 
 */
class ImageManager extends Base
{

    /**
     * @read
     */
    protected $_pathToImages;

    /**
     * @read
     */
    protected $_pathToThumbs;

    /**
     * @read
     */
    protected $_imageExtensions = array('gif', 'jpg', 'png', 'jpeg');

    /**
     * @readwrite
     */
    protected $_path;

    /**
     * @readwrite
     */
    protected $_thumbPath;

    /**
     * @readwrite
     */
    protected $_image = null;

    /**
     * @readwrite
     */
    protected $_fileName;

    /**
     * @readwrite
     */
    protected $_ext;

    /**
     * @readwrite
     */
    protected $_size;

    /**
     * @readwrite
     */
    protected $_imageType;

    /**
     * 
     */
    public function __construct()
    {
        $configuration = Registry::get('config');
        
        if (!empty($configuration->files->default)) {
            $this->_pathToImages = $configuration->files->default->pathToImages;
            $this->_pathToThumbs = $configuration->files->default->pathToThumbs;
        } else {
            throw new \Exception('Error in configuration file');
        }

        parent::__construct();
    }

    /**
     * 
     * @param type $filename
     */
    public function load($filename)
    {
        $this->path = $filename;
        $image_info = getimagesize($this->getPath());
        $this->imageType = $image_info[2];
        $this->fileName = str_replace(array('_large', '_thumb'), '', pathinfo($this->getPath(), PATHINFO_FILENAME));
        $this->ext = pathinfo($this->getPath(), PATHINFO_EXTENSION);
        $this->size = filesize($this->getPath());

        if ($this->imageType == IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($this->getPath());
        } elseif ($this->imageType == IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($this->getPath());
        } elseif ($this->imageType == IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($this->getPath());
        } else {
            throw new Exception\Type('Unsupported image type');
        }

        return $this;
    }

    /**
     * 
     * @param type $postField
     * @param type $namePrefix
     */
    public function upload($postField, $uploadto, $namePrefix = '')
    {
        $path = $this->getPathToImages() . $uploadto . '/';

        if (!is_dir('.' . $path)) {
            mkdir('.' . $path, 0666, true);
        }

        if (is_array($_FILES[$postField]['tmp_name'])) {
            $returnArray = array('photos' => array(), 'errors' => array());

            foreach ($_FILES[$postField]['name'] as $i => $name) {
                if (is_uploaded_file($_FILES[$postField]['tmp_name'][$i])) {
                    $size = $_FILES[$postField]['size'][$i];
                    $extension = pathinfo($_FILES[$postField]['name'][$i], PATHINFO_EXTENSION);
                    $filename = StringMethods::removeDiacriticalMarks(
                                    str_replace(' ', '_', pathinfo($_FILES[$postField]['name'][$i], PATHINFO_FILENAME)
                                    )
                    );

                    if ($size > 5000000) {
                        $returnArray['errors'][] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                        continue;
                    } else {
                        if (!in_array($extension, $this->_imageExtensions)) {
                            $returnArray['errors'][] = sprintf('%s Images can only be with jpg, jpeg, png or gif extension', $filename);
                            continue;
                        } else {
                            if (strlen($filename) > 50) {
                                $filename = substr($filename, 0, 50);
                            }

                            $imageName = $filename . '_large.' . $extension;
                            $imageLocName = $path . $namePrefix . $imageName;

                            if (file_exists('.' . $imageLocName)) {
                                $imageBackup = new self();
                                $imageBackup->load($imageLocName)->backup()->save();
                                unset($imageBackup);
                            }

                            $copy = move_uploaded_file($_FILES[$postField]['tmp_name'][$i], '.' . $imageLocName);

                            if (!$copy) {
                                $returnArray['errors'][] = sprintf('Error while uploading image %s. Try again.', $filename);
                                continue;
                            } else {
                                $newImage = new self();
                                $newImage->load($imageLocName);
                                $returnArray['photos'][] = $newImage;
                                unset($newImage);
                            }
                        }
                    }
                } else {
                    $i += 1;
                    $returnArray['errors'][] = sprintf("Source {$i} cannot be empty");
                    continue;
                }
            }

            return $returnArray;
        } else {
            if (is_uploaded_file($_FILES[$postField]['tmp_name'])) {
                $size = $_FILES[$postField]['size'];
                $extension = pathinfo($_FILES[$postField]['name'], PATHINFO_EXTENSION);
                $filename = StringMethods::removeDiacriticalMarks(
                                str_replace(' ', '_', pathinfo($_FILES[$postField]['name'], PATHINFO_FILENAME)
                                )
                );

                if ($size > 5000000) {
                    throw new Exception(sprintf('Your file %s size exceeds the maximum size limit', $filename));
                } else {
                    if (!in_array($extension, $this->_imageExtensions)) {
                        throw new Exception(sprintf('%s Images can only be with jpg, jpeg, png or gif extension', $filename));
                    } else {
                        if (strlen($filename) > 50) {
                            $filename = substr($filename, 0, 50);
                        }

                        $imageName = $filename . '_large.' . $extension;
                        $imageLocName = $path . $namePrefix . $imageName;

                        if (file_exists('.' . $imageLocName)) {
                            $imageBackup = new self();
                            $imageBackup->load($imageLocName)->backup()->save();
                            unset($imageBackup);
                        }

                        $copy = move_uploaded_file($_FILES[$postField]['tmp_name'], '.' . $imageLocName);

                        if (!$copy) {
                            throw new Exception(sprintf('Error while uploading image %s. Try again.', $filename));
                        } else {
                            $this->load($imageLocName);
                            return $this;
                        }
                    }
                }
            } else {
                throw new Exception('Source cannot be empty');
            }
        }
    }

    /**
     * 
     * @param type $newname
     * @return \THCFrame\Filesystem\ImageManager
     */
    public function backup()
    {
        if (null !== $this->getImage()) {
            $newname = str_replace('_large', '', $this->getFileName());

            if (strlen($newname) > 50) {
                $newname = substr($newname, 0, 50);
            }
            $newname.= '_backup';

            $img = dirname($this->getPath()) . '/' . $newname . '.' . $this->getExt();

            if (file_exists($img)) {
                unlink($img);
            }

            $this->fileName = $newname;
        } else {
            throw new Exception\Argument('Image is not loaded');
        }

        return $this;
    }

    /**
     * 
     * @param type $filename
     * @param type $image_type
     * @param type $compression
     * @param type $permissions
     * @throws Exception\Type
     */
    public function save($thumb = false, $compression = 90, $permissions = null)
    {
        if ($thumb) {
            $filename = dirname($this->getPath()) . '/'
                    . $this->getFileName()
                    . '_thumb.' . $this->getExt();

            $this->thumbPath = trim($filename, '.');
        } else {
            $filename = dirname($this->getPath()) . '/'
                    . $this->getFileName()
                    . '_large.' . $this->getExt();
        }

        if ($this->imageType == IMAGETYPE_JPEG) {
            imagejpeg($this->image, $filename, $compression);
        } elseif ($this->imageType == IMAGETYPE_GIF) {
            imagegif($this->image, $filename);
        } elseif ($this->imageType == IMAGETYPE_PNG) {
            imagepng($this->image, $filename);
        } else {
            throw new Exception\Type('Unsupported image type');
        }

        if ($permissions != null) {
            chmod($filename, $permissions);
        }
    }

    /**
     * The output function lets you output the image straight to the browser 
     * without having to save the file. 
     * Its useful for on the fly thumbnail generation
     * 
     * @param type $image_type
     * @throws Exception\Type
     */
    public function output($image_type = IMAGETYPE_JPEG)
    {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->_image);
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($this->_image);
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($this->_image);
        } else {
            throw new Exception\Type('Unsupported image type');
        }
    }

    /**
     * 
     * @return boolean
     */
    public function delete()
    {
        if (null === $this->getImage()) {
            return false;
        } else {
            unlink($this->getThumbPath());
            unlink($this->getPath());
            return true;
        }
    }

    /**
     * 
     * @return type
     */
    public function getWidth()
    {
        return imagesx($this->_image);
    }

    /**
     * 
     * @return type
     */
    public function getHeight()
    {
        return imagesy($this->_image);
    }

    /**
     * 
     * @return type
     */
    public function getPath($type = true)
    {
        if ($type) {
            if (file_exists($this->_path)) {
                return $this->_path;
            } elseif (file_exists('.' . $this->_path)) {
                return '.' . $this->_path;
            } elseif (file_exists('./' . $this->_path)) {
                return './' . $this->_path;
            }
        } else {
            return $this->_path;
        }
    }

    /**
     * 
     * @return type
     */
    public function getThumbPath($type = true)
    {
        if ($type) {
            if (file_exists($this->_thumbPath)) {
                return $this->_thumbPath;
            } elseif (file_exists('.' . $this->_thumbPath)) {
                return '.' . $this->_thumbPath;
            } elseif (file_exists('./' . $this->_thumbPath)) {
                return './' . $this->_thumbPath;
            }
        } else {
            return $this->_thumbPath;
        }
    }

    /**
     * 
     * @param type $height
     */
    public function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);

        return $this;
    }

    /**
     * 
     * @param type $width
     */
    public function resizeToWidth($width)
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);

        return $this;
    }

    /**
     * 
     * @param type $scale
     */
    public function scale($scale)
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);

        return $this;
    }

    /**
     * 
     * @param type $width
     * @param type $height
     */
    public function resize($width, $height)
    {
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->_image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->_image = $new_image;

        return $this;
    }

    /**
     * 
     */
    public function __destruct()
    {
        unset($this->_image);
    }

}

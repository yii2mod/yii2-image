<?php
/**
 * Created by PhpStorm.
 * User: semenov
 * Date: 16.07.14
 * Time: 15:17
 */

namespace yii2mod\image;

use Imagine\Image\Box;
use Imagine\Image\Point;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\imagine\Image as Imagine;

/**
 * Class Image
 * @package yii2mod\image
 */
class Image extends Component
{
    /**
     * @var string
     */
    public $password;

    /**
     * @var object Image
     */
    private $_image;

    /**
     * @var string relative path where the cache files are kept
     */
    public $cachePath = '/assets/img/';

    /**
     * @var int cache lifetime in seconds
     */
    public $cacheTime = 2592000;

    /**
     * Default offset for x coordinate
     * @var
     */
    public $defaultOffsetX = 0;

    /**
     * Default offset for y coordinate
     * @var
     */
    public $defaultOffsetY = 0;

    /**
     * Constructor.
     *
     * @param string $file
     * @param string $driver
     */
    public function __construct($file = null, $driver = null)
    {
        if (is_file($file)) {
            return $this->_image = Imagine::getImagine()->open(Yii::getAlias($file));
        }
        return "";
    }

    /**
     * Convert object to binary data of current image.
     * Must be rendered with the appropriate Content-Type header or it will not be displayed correctly.
     * @return string as binary
     */
    public function __toString()
    {
        try {
            return $this->image()->render();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * This method returns the current Image instance.
     * @return Image
     * @throws Exception
     */
    public function image()
    {
        if ($this->_image instanceof \Imagine\Imagick\Image || $this->_image instanceof \Imagine\Gd\Image) {
            return $this->_image;
        } else {
            throw new Exception('Don\'t have image');
        }
    }

    /**
     * This method detects which (absolute or relative) path is used.
     *
     * @param array $file path
     *
     * @return string path
     */
    public function detectPath($file)
    {
        $fullPath = Yii::getAlias('@app/web') . $file;
        if (is_file($fullPath)) {
            return $fullPath;
        }
        return $file;
    }

    /**
     * @param       $file
     * @param array $params
     *
     * @return string
     */
    public function getUrl($file, $params = array())
    {
        try {
            $hash = md5($file . serialize($params));
            $cachePath = Yii::getAlias('@app/web') . $this->cachePath . $hash{0};
            $cacheFileExt = isset($params['type']) ? $params['type'] : pathinfo($file, PATHINFO_EXTENSION);
            $cacheFileName = $hash . '.' . $cacheFileExt;
            $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $cacheFileName;
            $webCacheFile = Yii::$app->urlManager->baseUrl . $this->cachePath . $hash{0} . '/' . $cacheFileName;

            // Return URL to the cache image
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $this->cacheTime)) {
                return $webCacheFile;
            } else {
                $link = \Yii::$app->security->encryptByPassword(serialize(['f' => $file, 'p' => $params]), $this->password);
                return Yii::$app->urlManager->createAbsoluteUrl(['user/img', 'params' => $link]);
            }
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Performance of image manipulation and save result.
     *
     * @param string $file the path to the original image
     * @param string $newFile path to the resulting image
     * @param array $params
     *
     * @throws CException
     * @throws \yii\base\Exception
     * @return bool operation status
     */
    private function _doThumbOf($file, $newFile, $params)
    {
        if ($file instanceof \Imagine\Imagick\Image || $file instanceof \Imagine\Gd\Image) {
            $this->_image = $file;
        } else {
            if (!is_file($file)) {
                return false;
            }
            $this->_image = Imagine::getImagine()->open($this->detectPath($file));
        }
        //If empty params, generate basic thumbnail
        if (empty($params)) {
            return $this->image()->thumbnail(new Box($this->image()->getSize()->getWidth(), $this->image()->getSize()->getHeight()))->save($newFile);
        } else {
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'resize':
                        if (!isset($value['width']) || !isset($value['height'])) {
                            throw new Exception('Params "width" and "height" is required for action "' . $key . '"');
                        }
                        return $this->resize($value['width'], $value['height'], $newFile);
                        break;
                    case 'crop':
                        if (!isset($value['width']) || !isset($value['height'])) {
                            throw new Exception('Params "width" and "height" is required for action "' . $key . '"');
                        }
                        return $this->crop(
                            $value['width'],
                            $value['height'],
                            isset($value['offsetX']) ? $value['offsetX'] : $this->defaultOffsetX,
                            isset($value['offsetY']) ? $value['offsetY'] : $this->defaultOffsetY,
                            $newFile
                        );
                        break;
                    default:
                        throw new Exception('Action "' . $key . '" is not found');
                }
            }
        }
        return false;
    }

    /**
     * This method returns the URL to the cached thumbnail.
     *
     * @param string $file path
     * @param array $params
     *
     * @return string URL path
     */
    public function thumbSrcOf($file, $params = [])
    {
        try {
            // Paths
            $hash = md5($file . serialize($params));
            $cachePath = Yii::getAlias('@app/web') . $this->cachePath . $hash{0};
            $cacheFileExt = isset($params['type']) ? $params['type'] : pathinfo($file, PATHINFO_EXTENSION);
            $cacheFileName = $hash . '.' . $cacheFileExt;
            $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $cacheFileName;
            $webCacheFile = Yii::$app->urlManager->baseUrl . $this->cachePath . $hash{0} . '/' . $cacheFileName;

            // Return URL to the cache image
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $this->cacheTime)) {
                return $webCacheFile;
            }

            // Make cache dir
            FileHelper::createDirectory($cachePath);

            // Create and caching thumbnail use params
            if (!is_file($this->detectPath($file))) {
                return false;
            }

            $image = Imagine::getImagine()->open($this->detectPath($file));
            $this->_doThumbOf($image, $cacheFile, $params);
            unset($image);
            return $webCacheFile;
        } catch (Exception $e) {
            VarDumper::dump($e->getMessage(), 10, true);
            return '';
        }
    }

    /**
     * This method returns prepared HTML code for cached thumbnail.
     * Use standard yii-component CHtml::image().
     *
     * @param string $file path
     * @param array $params
     * @param array $htmlOptions
     *
     * @return string HTML
     */
    public function thumbOf($file, $params = [], $htmlOptions = [])
    {
        return Html::img(
            $this->getUrl($file, $params),
            isset($htmlOptions['alt']) ? $htmlOptions['alt'] : '',
            $htmlOptions
        );
    }

    /**
     * Description of the methods for the AutoComplete feature in a IDE
     * because it uses a design pattern "factory".
     */
    public function resize($width = null, $height = null, $newFile)
    {
        return $this->image()->thumbnail(new Box($width, $height))->save($newFile);
    }

    /**
     * @param $width
     * @param $height
     * @param $offsetX
     * @param $offsetY
     * @param $newFile
     * @return $this
     */
    public function crop($width, $height, $offsetX, $offsetY, $newFile)
    {
        return $this->image()->crop(new Point($offsetX, $offsetY), new Box($width, $height))->save($newFile);
    }

    /**
     * Renders empty image
     */
    public function renderEmpty()
    {
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
        Yii::$app->end();
    }
} 
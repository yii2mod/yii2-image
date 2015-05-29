<?php

namespace yii2mod\image;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\imagine\Image;

/**
 * Class Image
 * Database representation for image path:
 * /uploads/folder/imagename.jpg
 * @package yii2mod\image
 */
class ImageComponent extends Component
{
    /**
     * @var use png for cached files for transparency support
     */
    const TRANSPARENT_EXTENSION = 'png';
    /**
     * @var string
     */
    const IMAGE_ORIGINAL = 'original';

    /**
     * @var string relative path where the cache files are kept
     */
    public $cachePath = '/web/assets/image/';

    /**
     * @var string relative public path where the cache files are kept
     */
    public $cachePublicPath = '/assets/image/';

    /**
     * @var string system path to original image
     */
    public $sourcePath = '/uploads/Image/';

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
     * @var string path to image for no image
     */
    public $noImage = '@vendor/yii2mod/yii2-image/assets/no-image.png';

    /**
     * @var array
     */
    public $config = [
        'original' => [],
        'small' => [
            'thumbnail' => [
                'box' => [60, 60],
                'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND
            ]
        ],
        'medium' => [
            'thumbnail' => [
                'box' => [240, 240],
                'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND
            ]
        ],
    ];

    /**
     * Init component.
     */
    public function init()
    {
        $this->config = ArrayHelper::merge($this->config, isset(Yii::$app->params['image']) ? Yii::$app->params['image'] : []);
        return parent::init();
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
        if ($file == $this->noImage) {
            return Yii::getAlias($this->noImage);
        }
        $fullPath = $this->getImageSourcePath() . $file;
        if (is_file($fullPath)) {
            return $fullPath;
        }
        return false;
    }


    /**
     * @param $file
     * @param $type
     *
     * @return $this
     */
    public function getUrl($file, $type)
    {
        if (!$this->checkPermission($type)) {
            $file = $this->noImage;
        }

        $filePath = $this->getCachePath($file, $type);

        if (file_exists($filePath['system']) && (time() - filemtime($filePath['system']) < $this->cacheTime)) {
            return $filePath['public'];
        } else {
            return Url::toRoute(['/site/image', 'path' => urlencode($file), 'type' => $type]);
        }
    }

    /**
     * @param $file
     * @param $type
     *
     * @return array
     */
    private function getCachePath($file, $type)
    {
        $hash = md5($file . $type);
        if (isset($type) && isset($this->config[$type])) {
            $isTransparent = ArrayHelper::getValue($this->config[$type], 'transparent', false);
        } else {
            $isTransparent = false;
        }
        $cacheFileExt = $isTransparent ? self::TRANSPARENT_EXTENSION : strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $cachePath = $this->cachePath . $hash{0} . DIRECTORY_SEPARATOR;
        $cachePublicPath = $this->cachePublicPath . $hash{0} . DIRECTORY_SEPARATOR;
        $cacheFile = "{$hash}.{$cacheFileExt}";
        $systemPath = Yii::getAlias('@app') . $cachePath;
        if (!is_dir($systemPath)) {
            FileHelper::createDirectory($systemPath);
        }
        return [
            'system' => $systemPath . $cacheFile,
            'web' => $cachePath . $cacheFile,
            'public' => $cachePublicPath . $cacheFile,
            'extension' => $cacheFileExt
        ];
    }

    /**
     * @param $path
     * @param $type
     */
    public function show($path, $type = self::IMAGE_ORIGINAL)
    {
        if (!in_array($type, array_keys($this->config))) {
            $type = self::IMAGE_ORIGINAL;
        }
        if ($this->checkPermission($type)) {
            if ($file = $this->detectPath($path)) {
                $image = Image::getImagine()
                    ->open($file)
                    ->copy();
                $actions = $this->config[$type];
                ArrayHelper::remove($actions, 'transparent');
                foreach ($actions as $action => $options) {
                    if (method_exists($this, $action)) {
                        $image = $this->$action($image, $options);
                    }
                }
                $cachePath = $this->getCachePath($path, $type);
                if (!file_exists($cachePath['system'])) {
                    $image->save($cachePath['system']);
                }
                $image->show($cachePath['extension']);
                exit();
            }
        }
        $this->show($this->noImage, $type);
    }


    /**
     * @param $image
     * @param $options
     *
     * @return mixed
     */
    private function crop($image, $options)
    {
        return $image->crop(new Point($options['point'][0], $options['point'][1]), new Box($options['box'][0], $options['box'][1]));
    }

    /**
     * @param        $image
     * @param        $options
     * @param string $filter
     *
     * @return ImageInterface
     * @throws InvalidArgumentException
     */
    private function thumbnail($image, $options, $filter = ImageInterface::FILTER_UNDEFINED)
    {
        $width = $options['box'][0];
        $height = $options['box'][1];
        $size = $box = new Box($width, $height);
        $mode = $options['mode'];

        if ($mode !== ImageInterface::THUMBNAIL_INSET && $mode !== ImageInterface::THUMBNAIL_OUTBOUND) {
            throw new InvalidArgumentException('Invalid mode specified');
        }

        $imageSize = $image->getSize();
        $ratios = array(
            $size->getWidth() / $imageSize->getWidth(),
            $size->getHeight() / $imageSize->getHeight()
        );

        $image->strip();

        // if target width is larger than image width
        // AND target height is longer than image height
        if (!$size->contains($imageSize)) {
            if ($mode === ImageInterface::THUMBNAIL_INSET) {
                $ratio = min($ratios);
            } else {
                $ratio = max($ratios);
            }

            if ($mode === ImageInterface::THUMBNAIL_OUTBOUND) {
                if (!$imageSize->contains($size)) {
                    $size = new Box(
                        min($imageSize->getWidth(), $size->getWidth()),
                        min($imageSize->getHeight(), $size->getHeight())
                    );
                } else {
                    $imageSize = $image->getSize()->scale($ratio);
                    $image->resize($imageSize, $filter);
                }
                if ($ratios[0] > $ratios[1]) {
                    $cropPoint = new Point(0, 0);
                } else {
                    $cropPoint = new Point(
                        max(0, round(($imageSize->getWidth() - $size->getWidth()) / 2)),
                        max(0, round(($imageSize->getHeight() - $size->getHeight()) / 2))
                    );
                }

                $image->crop($cropPoint, $size);
            } else {
                if (!$imageSize->contains($size)) {
                    $imageSize = $imageSize->scale($ratio);
                    $image->resize($imageSize, $filter);
                } else {
                    $imageSize = $image->getSize()->scale($ratio);
                    $image->resize($imageSize, $filter);
                }
            }
        }

        // create empty image to preserve aspect ratio of thumbnail
        $palette = new RGB();
        $color = $palette->color('#000', 0); //transparent png with imagick
        $thumb = Image::getImagine()->create($box, $color);

        // calculate points
        $size = $image->getSize();

        $startX = 0;
        $startY = 0;
        if ($size->getWidth() < $width) {
            $startX = ceil($width - $size->getWidth()) / 2;
        }
        if ($size->getHeight() < $height) {
            $startY = ceil($height - $size->getHeight()) / 2;
        }

        $thumb->paste($image, new Point($startX, $startY));

        return $thumb;
    }

    /**
     * @param $image
     * @param $options
     *
     * @return mixed
     */
    private function watermark($image, $options)
    {
        $watermarkFilename = $options['watermarkFilename'];
        $watermark = Image::getImagine()->open(Yii::getAlias($watermarkFilename));
        $size = $image->getSize();
        $wSize = $watermark->getSize();

        $bottomRight = new Point($size->getWidth() - $wSize->getWidth(), $size->getHeight() - $wSize->getHeight());
        $image->paste($watermark, $bottomRight);

        return $image;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    private function checkPermission($type)
    {
        if (isset($this->config[$type]['visible'])) {
            $role = $this->config[$type]['visible'];
            unset($this->config[$type]['visible']);
            if (!Yii::$app->getUser()->can($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    private function getImageSourcePath()
    {
        return Yii::getAlias('@app') . $this->sourcePath;
    }
}

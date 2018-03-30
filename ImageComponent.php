<?php

namespace yii2mod\image;

use Imagine\Exception\InvalidArgumentException;
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
 *
 * @package yii2mod\image
 */
class ImageComponent extends Component
{
    /**
     * @var string use png for cached files for transparency support
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
     * @var string route to yii2mod\image\actions\ImageAction
     */
    public $imageAction = '/site/image';

    /**
     * @var int cache lifetime in seconds
     */
    public $cacheTime = 2592000;

    /**
     * Default offset for x coordinate
     *
     * @var
     */
    public $defaultOffsetX = 0;

    /**
     * Default offset for y coordinate
     *
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
                'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND,
            ],
        ],
        'medium' => [
            'thumbnail' => [
                'box' => [240, 240],
                'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND,
            ],
        ],
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->config = ArrayHelper::merge($this->config, Yii::$app->params['image'] ?? []);

        parent::init();
    }

    /**
     * This method detects which (absolute or relative) path is used.
     *
     * @param array $file path
     *
     * @return string path
     */
    public function detectPath($file): string
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
     * Get image url
     *
     * @param $file
     * @param $type
     *
     * @return string
     */
    public function getUrl($file, $type): string
    {
        if (!$this->checkPermission($type)) {
            $file = $this->noImage;
        }

        $filePath = $this->getCachePath($file, $type);

        if (file_exists($filePath['system']) && (time() - filemtime($filePath['system']) < $this->cacheTime)) {
            return $filePath['public'];
        }

        return Url::toRoute([$this->imageAction, 'path' => urlencode($file), 'type' => $type]);
    }

    /**
     * Get image cache path
     *
     * @param $file
     * @param $type
     *
     * @return array
     */
    protected function getCachePath($file, $type): array
    {
        $hash = md5($file . $type);
        if (isset($this->config[$type])) {
            $isTransparent = ArrayHelper::getValue($this->config[$type], 'transparent', false);
        } else {
            $isTransparent = false;
        }

        $cacheFileExt = $isTransparent ? self::TRANSPARENT_EXTENSION : strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $cachePath = $this->cachePath . $hash[0] . DIRECTORY_SEPARATOR;
        $cachePublicPath = $this->cachePublicPath . $hash[0] . DIRECTORY_SEPARATOR;
        $cacheFile = "{$hash}.{$cacheFileExt}";
        $systemPath = Yii::getAlias('@app') . $cachePath;

        FileHelper::createDirectory($systemPath);

        return [
            'system' => $systemPath . $cacheFile,
            'web' => $cachePath . $cacheFile,
            'public' => $cachePublicPath . $cacheFile,
            'extension' => $cacheFileExt,
        ];
    }

    /**
     * Show image
     *
     * @param $path
     * @param $type
     */
    public function show($path, $type = self::IMAGE_ORIGINAL)
    {
        if (!array_key_exists($type, $this->config)) {
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
                Yii::$app->end();
            }
        }

        $this->show($this->noImage, $type);
    }

    /**
     * Crop image
     *
     * @param $image \Imagine\Imagick\Image
     * @param $options
     *
     * @return mixed
     */
    protected function crop($image, $options)
    {
        return $image->crop(new Point($options['point'][0], $options['point'][1]), new Box($options['box'][0], $options['box'][1]));
    }

    /**
     * Create thumbnail
     *
     * @param $image \Imagine\Imagick\Image
     * @param $options
     * @param string $filter
     *
     * @return ImageInterface
     *
     * @throws InvalidArgumentException
     */
    protected function thumbnail($image, $options, $filter = ImageInterface::FILTER_UNDEFINED)
    {
        $width = $options['box'][0];
        $height = $options['box'][1];
        $size = $box = new Box($width, $height);
        $mode = $options['mode'];

        if ($mode !== ImageInterface::THUMBNAIL_INSET && $mode !== ImageInterface::THUMBNAIL_OUTBOUND) {
            throw new InvalidArgumentException('Invalid mode specified');
        }

        $imageSize = $image->getSize();
        $ratios = [
            $size->getWidth() / $imageSize->getWidth(),
            $size->getHeight() / $imageSize->getHeight(),
        ];

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
     * Add watermark
     *
     * @param $image \Imagine\Imagick\Image
     * @param $options
     *
     * @return mixed
     */
    protected function watermark($image, $options)
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
     * Check permission for current user
     *
     * @param $type
     *
     * @return bool
     */
    protected function checkPermission($type)
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
     * Get image source path
     *
     * @return string
     */
    protected function getImageSourcePath()
    {
        return Yii::getAlias('@app') . $this->sourcePath;
    }
}

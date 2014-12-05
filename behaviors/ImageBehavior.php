<?php
namespace yii2mod\image\behaviors;

use Yii;
use yii\base\Behavior;

/**
 * Class ImageBehavior
 * @package yii2mod\image\behaviors
 */
class ImageBehavior extends Behavior
{
    /**
     * @var string
     */
    public $pathAttribute = 'path';

    /**
     * @param $mode
     *
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function url($mode = 'original')
    {
        $attribute = $this->owner->pathAttribute;
        return Yii::$app->get('image')->getUrl($attribute, $mode);
    }

}
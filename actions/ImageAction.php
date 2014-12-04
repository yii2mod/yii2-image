<?php
namespace yii2mod\image\actions;

use Yii;
use yii\base\Action;

/**
 * Class ImageAction
 * @package yii2mod\image\actions
 */
class ImageAction extends Action
{

    /**
     * @param $path
     * @param $type
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function run($path, $type)
    {
        $path = urldecode($path);
        $image = Yii::$app->get('image');
        try {
            $image->show($path, $type);
        } catch (Exception $e) {
            $image->renderEmpty();
        }
    }


}
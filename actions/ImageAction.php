<?php

namespace yii2mod\image\actions;

use Yii;
use yii\base\Action;
use yii\base\Exception;

/**
 * Class ImageAction
 *
 * @package yii2mod\image\actions
 */
class ImageAction extends Action
{
    /**
     * Run action
     *
     * @param $path
     * @param $type
     */
    public function run($path, $type)
    {
        $path = urldecode($path);
        $image = Yii::$app->get('image');
        try {
            $image->show($path, $type);
        } catch (Exception $e) {
            Yii::error($e->getMessage());
        }
    }
}

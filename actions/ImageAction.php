<?php
/**
 * Created by PhpStorm.
 * User: semenov
 * Date: 16.07.14
 * Time: 15:18
 */

namespace yii2mod\image\actions;

use yii\base\Action;
use yii\imagine\Image;
use Yii;
use yii\log\Logger;

/**
 * Class ImageAction
 * @package yii2mod\image\actions
 */
class ImageAction extends Action
{
    /**
     * @param $params
     */
    public function run($params)
    {
        $image = Yii::$app->get('image');
        try {
            //decrypt
	        $params = \Yii::$app->security->decryptByPassword($params, $image->password);
            $params = @unserialize(rtrim($params, "\0"));
            $imageUrl = $image->thumbSrcOf($params['f'], $params['p']);
            $filePath = $image->detectPath($imageUrl);
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($filePath) {
                Image::getImagine()->open($filePath)->show($fileExtension);
                Yii::$app->end();
            } else {
                $image->renderEmpty();
            }
        } catch (Exception $e) {
            Yii::getLogger()->log($e->getMessage(), Logger::LEVEL_ERROR);
        }
        Yii::getLogger()->log(serialize($_GET), Logger::LEVEL_ERROR);
    }


}
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
        $test = [
            'params' => [
                'image' => [
                    'medium' => [
                        'thumbnail' => [
                            'box' => [194, 194],
                            'mode' => 'outbound'
                        ],
                        'visible' => 'user', //checking role before outputing url
                    ],
                    'home' => [
                        'thumbnail' => [
                            'box' => [640, 480],
                            'mode' => 'inset',
                        ],
                        'watermark' => [
                            'watermarkFilename' => '@app/web/images/watermark.png',
                        ],
                    ],
                ],
            ],
        ];
        $path = urldecode($path);
        $image = Yii::$app->get('image');
        try {
            $image->show($path, $type);
        } catch (Exception $e) {
            Yii::error($e->getMessage());
        }
    }
}

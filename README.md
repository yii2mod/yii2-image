Yii2 Image Component
==========

Provides methods for the dynamic manipulation of images. Various image formats such as JPEG, PNG, and GIF can be resized, cropped, rotated.

[![Latest Stable Version](https://poser.pugx.org/yii2mod/yii2-image/v/stable)](https://packagist.org/packages/yii2mod/yii2-image) [![Total Downloads](https://poser.pugx.org/yii2mod/yii2-image/downloads)](https://packagist.org/packages/yii2mod/yii2-image) [![License](https://poser.pugx.org/yii2mod/yii2-image/license)](https://packagist.org/packages/yii2mod/yii2-image)

Installation   
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2mod/yii2-image "*"
```

or add

```json
"yii2mod/yii2-image": "*"
```

to the require section of your composer.json.

Usage
-----

To use this extension, you have to configure the Connection class in your application configuration:

```php
//configure component:
return [
    //....
    'components' => [
        'image' => [
            'class' => 'yii2mod\image\ImageComponent',
        ],
    ]
];

//add behavior to the model 
public function behaviors()
    {
        return [
            'image' => [
                'class' => ImageBehavior::className(),
                'pathAttribute' => 'path'
            ],
        ];
    }
    
// add image action to SiteController
public function actions()
{
    return [
        'image' => 'yii2mod\image\actions\ImageAction'
    ];
}
 
```
Usage example:
```php
$imageModel->url('home'); // home is the type of photo, depending on type resize/crop/watermark/etc actions will happen
```

Configuring image types (yii params configuration section should be used):
```php
'params' => [
        .....
        'image' => [
            'medium' => [
                'thumbnail' => [
                    'box' => [194, 194],
                    'mode' => 'outbound'
                ],
                'visible' => 'user' //checking role before outputing url
            ],
            'home' => [
                'thumbnail' => [
                    'box' => [640, 480],
                    'mode' => 'inset'
                ],
                'watermark' => [
                    'watermarkFilename' => '@app/web/images/watermark.png'
                ],

            ]
        ]
    ],
```

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2mod/yii2-image "*"
```

or add

```json
"yii2mod/yii2-image": "*"
```

to the require section of your composer.json.

<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii2 Image Extension</h1>
    <br>
</p>

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

```
"yii2mod/yii2-image": "*"
```

to the require section of your composer.json.

Configuration
-------------

**Component Setup**

To use the Image Component, you need to configure the components array in your application configuration:
```php
'components' => [
    'image' => [
        'class' => 'yii2mod\image\ImageComponent',
    ],
],
```

**Attach the behavior to the model**

You need to add the `ImageBehavior` to the your model.
```php
public function behaviors()
{
    return [
        'image' => [
            'class' => ImageBehavior::class,
            'pathAttribute' => 'path',
        ],
    ];
}
```

**Action Setup**

You need to add the `ImageAction` to the your controller.
```php
public function actions()
{
    return [
        'image' => 'yii2mod\image\actions\ImageAction'
    ];
}
```

**Configuring image types**

Next, you should configure your params section in your configuration file:
```php
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
```

Usage:
------

```php
$model = Model::find()->one();
echo $model->url('medium'); // home is the type of photo.
```


## Support us

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/yii2mod). 
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

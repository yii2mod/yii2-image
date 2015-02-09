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
     * @var string
     */
    public $methodName = 'url';

    /**
     * @var array
     */
    private $_methods = [];

    /**
     * @attaching method to behavior
     */
    public function init()
    {
        $this->attachMethod($this->methodName, function ($mode = 'original') {
            $attribute = $this->owner->{$this->pathAttribute};
            return Yii::$app->get('image')->getUrl($attribute, $mode);
        });
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($name, $parameters)
    {
        if (isset($this->_methods[$name])) {
            return call_user_func_array($this->_methods[$name], $parameters);
        }
        return parent::__call($name, $parameters);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasMethod($name)
    {
        if ($name === $this->methodName) {
            return is_callable([$this, $name]);
        }
        return parent::hasMethod($name);
    }

    /**
     * @param $name
     * @param $closure
     */
    protected function attachMethod($name, $closure)
    {
        $this->_methods[$name] = \Closure::bind($closure, $this);
    }


}

<?php
namespace unapi\anticaptcha\twocaptcha;

use unapi\anticaptcha\twocaptcha\task\ImageTaskDecorator;

use unapi\anticaptcha\common\AnticaptchaTaskInterface;
use unapi\anticaptcha\common\task\ImageTask;

class Factory
{
    /**
     * @param AnticaptchaTaskInterface $task
     * @return AnticaptchaTaskInterface
     */
    public static function decorate(AnticaptchaTaskInterface $task): AnticaptchaTaskInterface
    {
        if ($task instanceof ImageTask)
            return new ImageTaskDecorator($task);

        return $task;
    }
}
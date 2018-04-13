<?php
namespace unapi\anticaptcha\twocaptcha\task;

use unapi\anticaptcha\common\AnticaptchaTaskInterface;
use unapi\anticaptcha\common\task\ImageTask;

class ImageTaskDecorator implements AnticaptchaTaskInterface
{
    const TYPE = 'ImageToTextTask';
    /** @var ImageTask */
    private $task;
    /**
     * @param ImageTask $task
     */
    public function __construct(ImageTask $task)
    {
        $this->task = $task;
    }
    /**
     * @return ImageTask
     */
    public function getTask(): ImageTask
    {
        return $this->task;
    }
    /**
     * @return string[]
     */
    public function asArray(): array
    {
        return [
            'method' => 'base64',
            'body' => base64_encode($this->getTask()->getBody()),
            'numeric' => $this->getTask()->getNumeric(),
            'min_len' => $this->getTask()->getMinLength(),
            'max_len' => $this->getTask()->getMaxLength(),
        ];
    }
}
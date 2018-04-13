<?php
namespace unapi\anticaptcha\twocaptcha;

class Client extends \GuzzleHttp\Client
{
    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config['base_uri'] = 'http://2captcha.com/';

        if (!array_key_exists('delay', $config))
            $config['delay'] = 2000;

        parent::__construct($config);
    }
}
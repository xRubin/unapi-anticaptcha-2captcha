<?php
namespace unapi\anticaptcha\twocaptcha;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use unapi\anticaptcha\common\AnticaptchaServiceInterface;
use unapi\anticaptcha\common\AnticaptchaTaskInterface;
use unapi\anticaptcha\common\dto\CaptchaSolvedDto;

class Service implements AnticaptchaServiceInterface, LoggerAwareInterface
{
    /** @var array Api key */
    private $key;
    /** @var Client */
    private $client;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $responseClass = CaptchaSolvedDto::class;
    /** @var int */
    private $softId;
    /** @var int */
    private $language = 0;
    /** @var int */
    private $retryCount = 20;
    /**
     * @param array $config Service configuration settings.
     */
    public function __construct(array $config = [])
    {
        if (isset($config['key'])) {
            $this->key = $config['key'];
        } else {
            throw new \InvalidArgumentException('Antigate api key required');
        }

        if (!isset($config['client'])) {
            $this->client = new Client();
        } elseif ($config['client'] instanceof Client) {
            $this->client = $config['client'];
        } else {
            throw new \InvalidArgumentException('Client must be instance of Client');
        }

        if (!isset($config['logger'])) {
            $this->logger = new NullLogger();
        } elseif ($config['logger'] instanceof LoggerInterface) {
            $this->setLogger($config['logger']);
        } else {
            throw new \InvalidArgumentException('Logger must be instance of LoggerInterface');
        }

        if (isset($config['responseClass']))
            $this->responseClass = $config['responseClass'];

        if (isset($config['softId'])) {
            $this->softId = $config['softId'];
        }

        if (isset($config['language'])) {
            $this->language = $config['language'];
        }
        if (isset($config['retryCount'])) {
            $this->retryCount = $config['retryCount'];
        }
    }
    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param AnticaptchaTaskInterface $task
     * @return PromiseInterface
     */
    public function resolve(AnticaptchaTaskInterface $task): PromiseInterface
    {
        $this->logger->debug('Start antigate service');
        return $this->client->requestAsync('POST', '/in.php', [
            'form_params' => [
                'key' => $this->key,
                'soft_id' => $this->softId,
                'language' => $this->language,
            ] + Factory::decorate($task)->asArray()
        ])->then(function (ResponseInterface $response) {
            $answer = $response->getBody()->getContents();
            $this->logger->debug('Upload answer: {answer}', ['answer' => $answer]);

            if (substr($answer, 0, 2) !== 'OK') {
                $this->logger->debug('Rejected with error {errorCode}', [
                    'errorCode' => $answer,
                ]);
                return new RejectedPromise($answer);
            }

            return $this->checkReady(substr($answer, 3), 0);
        });
    }
    /**
     * @param string $taskId
     * @param int $cnt
     * @return PromiseInterface
     */
    protected function checkReady(string $taskId, int $cnt): PromiseInterface
    {
        if ($cnt > $this->retryCount)
            return new RejectedPromise('Attempts exceeded');

        $this->logger->debug('Checking anticaptcha {taskId} ready (attempt = {attempt})', ['taskId' => $taskId, 'attempt' => $cnt]);
        return $this->client->requestAsync('GET', '/res.php', [
            'query' => [
                'key' => $this->key,
                'action' => 'get',
                'id' => $taskId,
            ],
        ])->then(function (ResponseInterface $response) use ($taskId, $cnt) {
            $answer = $response->getBody()->getContents();
            $this->logger->debug('Task {taskId} status: {answer}', ['taskId' => $taskId, 'answer' => $answer]);

            if ('CAPCHA_NOT_READY' === $answer)
                return $this->checkReady($taskId, ++$cnt);

            if (substr($answer, 0, 2) !== 'OK') {
                $this->logger->debug('Rejected with error {errorCode}', [
                    'errorCode' => $answer,
                ]);
                return new RejectedPromise($answer);
            }

            return new FulfilledPromise($this->responseClass::toDto([
                'code' => substr($answer, 3)
            ]));
        });
    }
}
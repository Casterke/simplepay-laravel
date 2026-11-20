<?php

declare(strict_types=1);

namespace Casterke\SimplePayLaravel\SDK;

use Casterke\SimplePayLaravel\SDK\Trait\Communication;
use Casterke\SimplePayLaravel\SDK\Trait\Logger;
use Casterke\SimplePayLaravel\SDK\Trait\Signature;
use Casterke\SimplePayLaravel\SDK\Trait\Views;

/**
 * Base class for SimplePay implementation
 *
 * @category SDK
 *
 * @author   SimplePay IT Support <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 *
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */
class Base
{
    use Signature;
    use Communication;
    use Views;
    use Logger;

    public $config = [];

    public $sdkVersion = 'SimplePay_PHP_SDK_2.1.1_210804';

    public $logTransactionId = 'N/A';

    public $logOrderRef = 'N/A';

    public $logPath = '';

    protected $headers = [];

    protected $hashAlgo = 'sha384';

    protected $logSeparator = '|';

    protected $logContent = [];

    protected $debugMessage = [];

    protected $currentInterface = '';

    protected $api = [
        'sandbox' => 'https://sandbox.simplepay.hu/payment',
        'live' => 'https://secure.simplepay.hu/payment',
    ];

    protected $apiInterface = [
        'start' => '/v2/start',
        'finish' => '/v2/finish',
        'refund' => '/v2/refund',
        'query' => '/v2/query',
    ];

    protected $phpVersion = 8;

    // Declared explicitly: dynamic properties are deprecated since PHP 8.2.
    // Subclasses may redeclare these with defaults (same or wider visibility).
    protected $transactionBase = [];

    protected $returnData = [];

    protected $request = [];

    public $content;

    public $result;

    public $curlInfo;

    public $confirmContent;

    public $signature;

    public $notificationFormated;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->logContent['runMode'] = strtoupper($this->currentInterface);
        $ver = (float) phpversion();
        $this->logContent['phpVersion'] = $ver;
        if (is_numeric($ver)) {
            if ($ver < $this->phpVersion) {
                $this->phpVersion = $ver;
            }
        }
    }

    /**
     * Add unique config field
     *
     * @param  string  $key   Config field name
     * @param  string  $value Vonfig field value
     * @return void
     */
    public function addConfigData($key = '', $value = '')
    {
        if ($key == '') {
            $key = 'EMPTY_CONFIG_KEY';
        }
        $this->config[$key] = $value;
    }

    /**
     * Add complete config array
     *
     * @param  string  $config Populated config array
     * @return void
     */
    public function addConfig($config = [])
    {
        foreach ($config as $configKey => $configValue) {
            $this->config[$configKey] = $configValue;
        }
    }

    /**
     * Add uniq transaction field
     *
     * @param  string  $key   Data field name
     * @param  string  $value Data field value
     * @return void
     */
    public function addData($key = '', $value = '')
    {
        if ($key == '') {
            $key = 'EMPTY_DATA_KEY';
        }
        $this->transactionBase[$key] = $value;
    }

    /**
     * Add data to a group
     *
     * @param  string  $group Data group name
     * @param  string  $key   Data field name
     * @param  string  $value Data field value
     * @return void
     */
    public function addGroupData($group = '', $key = '', $value = '')
    {
        if (! isset($this->transactionBase[$group])) {
            $this->transactionBase[$group] = [];
        }
        $this->transactionBase[$group][$key] = $value;
    }

    /**
     * Add item to pay
     *
     * @param  string  $itemData A product or service for pay
     * @return void
     */
    public function addItems($itemData = [])
    {
        $item = [
            'ref' => '',
            'title' => '',
            'description' => '',
            'amount' => 0,
            'price' => 0,
            'tax' => 0,
        ];

        if (! isset($this->transactionBase['items'])) {
            $this->transactionBase['items'] = [];
        }

        foreach ($itemData as $itemKey => $itemValue) {
            $item[$itemKey] = $itemValue;
        }
        $this->transactionBase['items'][] = $item;
    }

    /**
     * Shows transaction base data
     *
     * @return array $this->transactionBase Transaction data
     */
    public function getTransactionBase()
    {
        return $this->transactionBase;
    }

    /**
     * Shows API call return data
     *
     * @return array $this->returnData Return data
     */
    public function getReturnData()
    {
        return $this->convertToArray($this->returnData);
    }

    /**
     * Shows transactional log
     *
     * @return array $this->logContent Transactional log
     */
    public function getLogContent()
    {
        return $this->logContent;
    }

    /**
     * Check data if JSON, or set data to JSON
     *
     * @param  string  $data Data
     * @return string JSON encoded data
     */
    public function checkOrSetToJson($data = '')
    {
        $json = '[]';
        //empty
        if ($data === '') {
            $json = json_encode([]);

            return $json;
        }
        //array
        if (is_array($data)) {
            $json = json_encode($data);

            return $json;
        }
        //object
        if (is_object($data)) {
            $json = json_encode($data);

            return $json;
        }
        //json
        $result = json_decode($data);
        if ($result !== null) {
            $json = $data;

            return $json;
        }
        //serialized
        $result = unserialize($data);
        if ($result !== false) {
            $json = json_encode($result);

            return $json;
        }
    }

    /**
     * Serves header array
     *
     * @param  string  $hash     Signature for validation
     * @param  string  $language Landuage of content
     * @return array Populated header array
     */
    protected function getHeaders($hash = '', $language = 'en')
    {
        $headers = [
            'Accept-language: ' . $language,
            'Content-type: application/json',
            'Signature: ' . $hash,
        ];

        return $headers;
    }

    /**
     * Random string generation for salt
     *
     * @param  int  $length Lemgth of random string, default 32
     * @return string Random string
     */
    protected function getSalt($length = 32)
    {
        $saltBase = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 0; $i <= $length; $i++) {
            $saltBase .= substr($chars, rand(1, strlen($chars)), 1);
        }

        return hash('md5', $saltBase);
    }

    /**
     * API URL settings depend on function
     *
     * @return void
     */
    protected function setApiUrl()
    {
        $api = 'live';
        if (isset($this->config['api'])) {
            $api = $this->config['api'];
        }
        $this->config['apiUrl'] = $this->api[$api] . $this->apiInterface[$this->currentInterface];
    }

    /**
     * Convert object to array
     *
     * @param  object  $obj Object to transform
     * @return array $new Result array
     */
    protected function convertToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        $new = $obj;
        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = $this->convertToArray($val);
            }
        }

        return $new;
    }

    /**
     * Creates a 1-dimension array from a 2-dimension one
     *
     * @param  array  $arrayForProcess Array to be processed
     * @return array $return          Flat array
     */
    protected function getFlatArray($arrayForProcess = [])
    {
        $array = $this->convertToArray($arrayForProcess);
        $return = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $subArray = $this->getFlatArray($value);
                foreach ($subArray as $subKey => $subValue) {
                    $return[$key . '_' . $subKey] = $subValue;
                }
            } elseif (! is_array($value)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Set config variables
     *
     * @return void
     */
    protected function setConfig()
    {
        if (isset($this->transactionBase['currency']) && $this->transactionBase['currency'] != '') {
            $this->config['merchant'] = $this->config[$this->transactionBase['currency'] . '_MERCHANT'];
            $this->config['merchantKey'] = $this->config[$this->transactionBase['currency'] . '_SECRET_KEY'];
        } elseif (isset($this->config['merchantAccount'])) {
            foreach ($this->config as $configKey => $configValue) {
                if ($configValue === $this->config['merchantAccount']) {
                    $key = $configKey;
                    break;
                }
            }
            $this->transactionBase['currency'] = substr($key, 0, 3);
            $this->config['merchant'] = $this->config[$this->transactionBase['currency'] . '_MERCHANT'];
            $this->config['merchantKey'] = $this->config[$this->transactionBase['currency'] . '_SECRET_KEY'];
        }

        $this->config['api'] = 'live';
        if ($this->config['SANDBOX']) {
            $this->config['api'] = 'sandbox';
        }
        $this->logContent['environment'] = strtoupper($this->config['api']);

        $this->config['logger'] = false;
        if (isset($this->config['LOGGER'])) {
            $this->config['logger'] = $this->config['LOGGER'];
        }

        $this->config['logPath'] = 'log';
        if (isset($this->config['LOG_PATH'])) {
            $this->config['logPath'] = $this->config['LOG_PATH'];
        }

        $this->config['autoChallenge'] = false;
        if (isset($this->config['AUTOCHALLENGE'])) {
            $this->config['autoChallenge'] = $this->config['AUTOCHALLENGE'];
        }
    }

    /**
     * Transaction preparation
     *
     * All settings before start transaction
     *
     * @return void
     */
    protected function prepare()
    {
        $this->setConfig();
        $this->logContent['callState1'] = 'PREPARE';
        $this->setApiUrl();
        $this->transactionBase['merchant'] = $this->config['merchant'];
        $this->transactionBase['salt'] = $this->getSalt();
        $this->transactionBase['sdkVersion'] = $this->sdkVersion . ':' . hash_file('md5', __FILE__);
        $this->content = $this->getHashBase($this->transactionBase);
        $this->logContent = array_merge($this->logContent, $this->transactionBase);
        $this->config['computedHash'] = $this->getSignature($this->config['merchantKey'], $this->content);
        $this->headers = $this->getHeaders($this->config['computedHash'], 'EN');
    }

    /**
     * Execute API call and returns with result
     *
     * @return array $result
     */
    protected function execApiCall()
    {
        $this->prepare();
        $transaction = [];

        $this->logContent['callState2'] = 'REQUEST';
        $this->logContent['sendApiUrl'] = $this->config['apiUrl'];
        $this->logContent['sendContent'] = $this->content;
        $this->logContent['sendSignature'] = $this->config['computedHash'];

        $commRresult = $this->runCommunication($this->config['apiUrl'], $this->content, $this->headers);

        $this->logContent['callState3'] = 'RESULT';

        //call result
        $result = explode("\r\n", $commRresult);
        $transaction['responseBody'] = end($result);

        //signature
        foreach ($result as $resultItem) {
            $headerElement = explode(':', $resultItem);
            if (isset($headerElement[0]) && isset($headerElement[1])) {
                $header[$headerElement[0]] = $headerElement[1];
            }
        }
        $transaction['responseSignature'] = $this->getSignatureFromHeader($header);

        //check transaction validity
        $transaction['responseSignatureValid'] = false;
        if ($this->isCheckSignature($transaction['responseBody'], $transaction['responseSignature'])) {
            $transaction['responseSignatureValid'] = true;
        }

        //fill transaction data
        if (is_object(json_decode($transaction['responseBody']))) {
            foreach (json_decode($transaction['responseBody']) as $key => $value) {
                $transaction[$key] = $value;
            }
        }

        if (isset($transaction['transactionId'])) {
            $this->logTransactionId = $transaction['transactionId'];
        } elseif (isset($transaction['cardId'])) {
            $this->logTransactionId = $transaction['cardId'];
        }
        if (isset($transaction['orderRef'])) {
            $this->logOrderRef = $transaction['orderRef'];
        }

        $this->returnData = $transaction;
        $this->logContent = array_merge($this->logContent, $transaction);
        $this->logContent = array_merge($this->logContent, $this->getTransactionBase());
        $this->logContent = array_merge($this->logContent, $this->getReturnData());
        $this->writeLog();

        return $transaction;
    }
}

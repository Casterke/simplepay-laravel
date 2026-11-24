<?php

declare(strict_types=1);

namespace Casterke\SimplePayLaravel\SDK\Trait;

use Exception;

/**
 * Communication
 *
 * @category SDK
 *
 * @author   SimplePay IT Support <itsupport@otpmobil.com>
 * @license  http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 *
 * @link     http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */
trait Communication
{
    /**
     * Handler for cURL communication
     *
     * @param  string  $url     URL
     * @param  string  $data    Sending data to URL
     * @param  string  $headers Header information for POST
     * @return array Result of cURL communication
     */
    public function runCommunication($url = '', $data = '', $headers = [])
    {
        $result = '';
        $curlData = curl_init();
        curl_setopt($curlData, CURLOPT_URL, $url);
        curl_setopt($curlData, CURLOPT_POST, true);
        curl_setopt($curlData, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlData, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlData, CURLOPT_USERAGENT, 'curl');
        curl_setopt($curlData, CURLOPT_TIMEOUT, 60);
        curl_setopt($curlData, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlData, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlData, CURLOPT_HEADER, true);
        //cURL + SSL
        //curl_setopt($curlData, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($curlData, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($curlData);
        $this->result = $result;
        $this->curlInfo = curl_getinfo($curlData);
        try {
            if (curl_errno($curlData)) {
                throw new Exception(curl_error($curlData));
            }
        } catch (Exception $e) {
            $this->logContent['runCommunicationException'] = $e->getMessage();
        }
        if (PHP_VERSION_ID < 80000) {
            curl_close($curlData); // no-op since PHP 8.0, deprecated in 8.5
        }

        return $result;
    }
}

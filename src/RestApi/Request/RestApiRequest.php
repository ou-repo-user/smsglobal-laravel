<?php

namespace JoshuaChinemezu\SmsGlobal\RestApi\Request;

use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

/*
 * This file is part of the SmsGlobal Laravel Package.
 *
 * (c) Joshua Chinemezu <joshuachinemezu@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class RestApiRequest
{
    protected $host;
    protected $protocol;
    protected $port;
    protected $apiVersion;
    protected $extraData;
    protected $client;
    protected $url;
    protected $action;
    protected $timestamp;
    protected $nonce;
    protected $apiKey;
    protected $secretKey;
    protected $hashAlgo;
    protected $baseUrl;

    public function __construct($apiKey, $secretKey, $hashAlgo)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->hashAlgo = $hashAlgo;
        $this->setHost();
        $this->setProtocol();
        $this->setPort();
        $this->setApiVersion();
        $this->setBaseUrl();
        $this->setRequestClient();
    }

    /**
     * Get debug mode from SmsGlobal config file
     */
    public function setRequestClient()
    {
        $this->client = new Client();
    }

    /**
     * Get host from SmsGlobal config file
     */
    public function setHost()
    {
        $this->host = Config::get('smsglobal.host');
    }

    /**
     * Get protocol from SmsGlobal config file
     */
    public function setProtocol()
    {
        $this->protocol = strtolower(Config::get('smsglobal.protocol'));
    }

    /**
     * Get port from SmsGlobal config file
     */
    public function setPort()
    {
        $this->port = Config::get('smsglobal.port');
    }

    /**
     * Get api version from SmsGlobal config file
     */
    public function setApiVersion()
    {
        $this->apiVersion = Config::get('smsglobal.apiVersion');
    }

    public function setBaseUrl()
    {
        $this->baseUrl = "{$this->protocol}://{$this->host}/{$this->apiVersion}";
    }

    private function setRequestOptions($action, $method)
    {
        $this->method = $method;
        $this->action = $action;
        $this->url = "{$this->baseUrl}/{$this->action}";
    }

    public function get($action, $queryOptions = [])
    {
        $this->setRequestOptions($action, 'GET');
        try {
            $request = $this->client->get($this->url, [
                'headers' => $this->getAuthorisationHeader(),
                'query' => $queryOptions
            ],  array());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->getJsonResponse($e->getResponse());
            }
            return Psr7\str($e->getRequest());
        }
        return $this->getJsonResponse($request);
    }

    public function post($action, $formData = [])
    {
        $this->setRequestOptions($action, 'POST');
        try {
            $request = $this->client->post($this->url, [
                'form_params' => $formData,
                'headers' => $this->getAuthorisationHeader(),
            ], array());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->getJsonResponse($e->getResponse());
            }
            return Psr7\str($e->getRequest());
        }

        return $this->getJsonResponse($request);
    }

    public function put($action, $optionData = [])
    {
        $this->setRequestOptions($action, 'PUT');
        try {
            $request = $this->client->put($this->url, [
                'form_params' => $optionData,
                'headers' => $this->getAuthorisationHeader(),
            ], array());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->getJsonResponse($e->getResponse());
            }
            return Psr7\str($e->getRequest());
        }

        return $this->getJsonResponse($request);
    }

    public function patch($action, $optionData = [])
    {
        $this->setRequestOptions($action, 'PATCH');
        try {
            $request = $this->client->patch($this->url, [
                'form_params' => $optionData,
                'headers' => $this->getAuthorisationHeader(),
            ], array());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->getJsonResponse($e->getResponse());
            }
            return Psr7\str($e->getRequest());
        }

        return $this->getJsonResponse($request);
    }

    public function delete($action)
    {

        $this->setRequestOptions($action, 'DELETE');
        try {
            $request = $this->client->delete($this->url, [
                'headers' => $this->getAuthorisationHeader(),
            ], array());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->getJsonResponse($e->getResponse());
            }
            return Psr7\str($e->getRequest());
        }

        return $this->getJsonResponse($request);
    }

    private function getJsonResponse($request)
    {
        return json_decode($request->getBody());
    }

    private function generateRawString()
    {
        return $this->timestamp . "\n"
            . $this->nonce . "\n"
            . $this->method . "\n"
            . '/' . $this->apiVersion . '/' . $this->action . "\n"
            . $this->host . "\n"
            . $this->port . "\n"
            . $this->extraData . "\n";
    }

    private function generateNonce()
    {
        return md5(microtime() . mt_rand());
    }

    private function generateTimeStamp()
    {
        return time();
    }

    /**
     * @param $method
     * @param $action
     * @return array of HTTP headers in key/value pair
     */
    private function getAuthorisationHeader()
    {
        $this->timestamp = $this->generateTimeStamp();
        $this->nonce = $this->generateNonce();

        // dd($this->generateRawString());

        # Encrypt
        $hash = hash_hmac($this->hashAlgo, $this->generateRawString(), $this->secretKey, true);
        $hash = base64_encode($hash);

        return array(
            "Authorization" => sprintf('MAC id="%s", ts="%s", nonce="%s", mac="%s"', $this->apiKey, $this->timestamp, $this->nonce, $hash),
            'Accept' => 'application/json',
            'Content-Type'  => 'application/json',
        );
    }
}

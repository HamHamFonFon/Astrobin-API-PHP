<?php

namespace Astrobin;

use Astrobin\Exceptions\WsException;

/**
 * Class AstrobinWebService
 * @package AppBundle\Astrobin
 */
abstract class AbstractWebService
{
    const ASTROBIN_URL = 'https://www.astrobin.com/api/v1/';
    const MAX_REDIRS = 10;
    const LIMIT_MAX = 20;
    const TIMEYOUT = 30;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';

    protected $timeout;
    private $apiKey;
    private $apiSecret;
    /** @var CurlHttpRequestInterface */
    protected $curlRequest;

    /**
     * AbstractWebService constructor.
     */
    public function __construct()
    {
        $this->apiKey = getenv('ASTROBIN_API_KEY');
        $this->apiSecret = getenv('ASTROBIN_API_SECRET');
        $this->timeout = self::TIMEYOUT;
    }


    /**
     * @param $endPoint
     * @param $method
     * @param $data
     * @return mixed|null
     * @throws WsException
     */
    protected function call($endPoint, $method, $data): object
    {
        if (is_null($this->apiKey) || is_null($this->apiSecret)) {
            throw new WsException(sprintf("Astrobin Webservice : API key or API secret are null"));
        }

        $urlAstrobin = $this->buildUrl($endPoint, $data);
        $this->curlRequest = new CurlHttpRequest();
        $options = $this->initCurlOptions($method, $urlAstrobin);
        $this->curlRequest->setOptionArray($options);

        if (!$resp = $this->curlRequest->execute()) {
            if (empty($resp)) {
                $dataErr = (!is_array($data)) ? [$data] : $data;
                throw new WsException(sprintf("[Astrobin Response] Empty response, check data :\n %s", implode(' . ', $dataErr)));
            }
            // show problem and throw exception
            throw new WsException(
                sprintf("[Astrobin Response] HTTP Error (curl_exec) #%u: %s", $this->curlRequest->getErrNo(), $this->curlRequest->getError())
            );
        }
        $this->curlRequest->close();

        if (!$resp || empty($resp)) {
            throw new WsException("[Astrobin Response] Empty Json");
        }

        return $this->buildResponse($resp);
    }


    /**
     * Build the WebService URL
     * @param $endPoint
     * @param $data
     * @return string
     */
    private function buildUrl($endPoint, $data): string
    {
        // Build URL with params
        $url = self::ASTROBIN_URL . $endPoint;

        if (is_array($data) && 0 < count($data)) {
            $paramData = implode('&', array_map(function ($k, $v) {
                $formatValue = "%s";
                if (is_numeric($v)) {
                    $formatValue = "%d";
                }
                return sprintf("%s=$formatValue", $k, $v);
            }, array_keys($data), $data));

            $url .= '?' . $paramData;
        } else {
            if ('/' !== substr($url, strlen($url)-1, strlen($url))) {
                $url .= '/';
            }
            // Warning : the "/" before "?" is mandatory or else no response from WS...
            $url .= $data . '/?';
        }

        // Add keys and format
        $params = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'format' => 'json'
        ];

        $httpParams = implode('', array_map(function ($k, $v) {
            return sprintf("&%s=%s", $k, $v);
        }, array_keys($params), $params));
        $url .= $httpParams;

        return $url;
    }


    /**
     * Options for cURL request
     *
     * @param $method
     * @param string $url
     * @return mixed
     */
    protected function initCurlOptions($method, $url): array
    {
        // Options CURL
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => self::MAX_REDIRS,
            CURLOPT_HEADER => "Accept:application/json",
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        // GET
        if (self::METHOD_GET === $method) {
            $options = array_replace_recursive($options, [
                CURLOPT_CUSTOMREQUEST => self::METHOD_GET,
                CURLOPT_HTTPGET => true,
            ]);
        }

        return $options;
    }


    /**
     * Check response and jsondecode object
     *
     * @param $resp
     * @return object|null
     * @throws WsException
     */
    public function buildResponse($resp): object
    {
        $obj = null;

        if (is_string($resp)) {
            if (false === strpos($resp, '{', 0)) {
                // check if html
                if (false !== strpos($resp, '<html', 0)) {
                    throw new WsException(sprintf("[Astrobin Response] Response in HTML format :\n %s", $resp));
                }
                throw new WsException(sprintf("[Astrobin Response] Not a JSON valid format :\n %s", $resp));
            }
            $obj = json_decode($resp);
            if (JSON_ERROR_NONE != json_last_error()) {
                throw new WsException(
                    sprintf("[Astrobin ERROR] Error JSON :\n%s", json_last_error())
                );
            }
            if (array_key_exists('error', $obj)) {
                throw new WsException(
                    sprintf("[Astrobin ERROR] Response : %s", $obj->error)
                );
            }
        } else {
            throw new WsException("[Astrobin ERROR] Response is not a string, got ". gettype($resp) . " instead.");
        }

        return $obj;
    }
}

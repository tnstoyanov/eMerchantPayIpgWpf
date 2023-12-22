<?php

class ParamSigner
{
    public const SIGNATURE_TYPE_SHA256 = 'PSSHA256';
    public const SIGNATURE_TYPE_SHA1 = 'PSSHA1';
    public const SIGNATURE_TYPE_MD5 = 'PSMD5';

    private $secret = '';
    private $params;
    private $lifetime = 24;
    private $signatureType = self::SIGNATURE_TYPE_SHA256;

    /**
     * Set the shared secret
     * @param string $secret
     * @return ParamSigner
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Set the amount of time this URL will be valid for in hours.
     * @param int $lifetime
     * @return ParamSigner
     */
    public function setLifeTime(int $lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * Set a URL parameter
     * @param string $param
     * @param string $value
     * @return ParamSigner
     */
    public function setParam(string $param, string $value)
    {
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * Set multiple URL parameters
     * @param array $paramArray
     * @return ParamSigner
     */
    public function setParams(array $paramArray)
    {
        foreach ($paramArray as $param => $value){
            $this->setParam($param,$value);
        }
        return $this;
    }

    /**
     * Clear the param list
     * @return ParamSigner
     */
    public function clearParams()
    {
        $this->params = [];
        return $this;
    }

    /**
     * Get the signed query string
     * @return string
     * @throws Exception
     */
    public function getQueryString()
    {
        return $this->getSignature(true);
    }

    /**
     * Generate a signed query string in one shot
     * @param array $paramArray Associative array of name/value pairs to be signed
     * @param string $secret The secret key for the client if not previously set.
     * @return string
     * @throws Exception
     */
    public function generateQueryString(array $paramArray, string $secret = '')
    {
        if ($secret) {
            $this->setSecret($secret);
        }
        $this->clearParams();
        foreach ($paramArray as $name => $value) {
            $this->setParam($name, $value);
        }
        return $this->getQueryString();
    }


    /**
     * Generate a signed URL from an existing URL
     * @param string $url URL to be signed
     * @param string $secret The secret key for the client
     * @return string
     * @throws Exception
     */
    public function signURL(string $url, string $secret = '')
    {
        $p = parse_url($url);
        $temp = [];
        parse_str($p['query'], $temp);
        $querystring = $this->generateQueryString($temp, $secret);
        return $p['scheme'] . '://' . $p['host'] . $p['path'] . '?' . $querystring;
    }

    /**
     * Get the signature for the query string
     * @param boolean $returnQueryString default false
     * @return string
     * @throws Exception
     */
    public function getSignature(bool $returnQueryString = false)
    {
        $this->setParam('PS_EXPIRETIME', time() + (3600 * $this->lifetime));
        $this->setParam('PS_SIGTYPE', $this->signatureType);
        $sigstring = '';
        $urlencstring = '';
        ksort($this->params, SORT_STRING);
        foreach ($this->params as $key => $value) {
            $sigstring .= "&" . $key . '=' . $value;
            $urlencstring .= "&" . urlencode($key) . '=' . urlencode($value);
        }
        $signature = hash_hmac('sha256', ltrim($sigstring, '&'), $this->secret);
        if ($returnQueryString) {
            return 'PS_SIGNATURE=' . urlencode($signature) . $urlencstring;
        }
        return $signature;
    }

    /**
     * Authenticate the params using the secret key
     * @param array $paramArray
     * @param string $secret
     * @return array|false
     */
    public static function paramAuthenticate(array $paramArray, string $secret = '')
    {
        $sentSignature = $paramArray['PS_SIGNATURE'] ?? '';
        unset($paramArray['PS_SIGNATURE']);
        $string = '';
        ksort($paramArray, SORT_STRING);
        foreach ($paramArray as $key => $value) {
            $string .= "&" . $key . '=' . $value;
        }
        $signatureType = strtoupper($paramArray['PS_SIGTYPE'] ?? '');
        switch ($signatureType) {
            case 'MD5':
            case self::SIGNATURE_TYPE_MD5:
                $signature = md5($secret . $string);
                break;
            case 'SHA1':
            case self::SIGNATURE_TYPE_SHA1:
                $signature = sha1($secret . $string);
                break;
            case self::SIGNATURE_TYPE_SHA256:
                $signature = hash_hmac('sha256', ltrim($string, '&'), $secret);
                break;
            default:
                return false;
        }
        if ($sentSignature !== $signature) {
            return false;
        }
        unset($paramArray['PS_SIGTYPE'], $paramArray['PS_EXPIRETIME']);
        return $paramArray;
    }
}
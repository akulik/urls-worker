<?php

class Request
{
    private $ch;
    private $response;
    private $info;

    /**
     * Default user agent
     * @var array
     */
    private $userAgents = [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.76 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1866.237 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0',
        'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0'
    ];

    function __construct()
    {
        $this->ch = curl_init();

        // Output and redirect
        $this->setOpt(CURLINFO_HEADER_OUT, true);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_HEADER, false);
        $this->setOpt(CURLOPT_FOLLOWLOCATION, true);

        // SSL
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);

        // Cookie
        $this->setOpt(CURLOPT_COOKIESESSION, true);
        $this->setOpt(CURLOPT_COOKIEJAR, 'cookie');
        $this->setOpt(CURLOPT_COOKIEFILE, 'cookie');

        // Timeout
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, 600);
        $this->setOpt(CURLOPT_TIMEOUT, 600);

        // Set user agent
        shuffle($this->userAgents);
        $this->setUserAgent(array_pop($this->userAgents));
    }

    /**
     * Do a GET request
     *
     * @param  string $url
     *
     * @return string
     */
    public function get(string $url)
    {
        // Disable POST
        $this->setOpt(CURLOPT_POST, 0);

        $this->exec($url);

        return $this->getResponse();
    }

    /**
     * Get response body
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get response info
     * @return object
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Get response HTTP code
     * @return int
     */
    public function getCode()
    {
        return $this->info->http_code;
    }

    /**
     * If response was 200 OK
     * @return boolean
     */
    public function isOk()
    {
        return $this->getCode() === 200;
    }

    /**
     * Set user agent
     *
     * @param string $userAgent
     *
     * @return object
     */
    public function setUserAgent(string $userAgent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $userAgent);

        return $this;
    }

    /**
     * Set cURL option
     *
     * @param int $option
     * @param mixed $value
     *
     * @return object
     */
    public function setOpt(int $option, $value)
    {
        curl_setopt($this->ch, $option, $value);

        return $this;
    }

    /**
     * Exectue cURL
     * @param string $url
     * @return void
     */
    private function exec(string $url)
    {
        $this->setOpt(CURLOPT_FOLLOWLOCATION, false);
        $this->setOpt(CURLOPT_HTTPHEADER, []);
        $this->setOpt(CURLOPT_URL, $url);

        $this->response = curl_exec($this->ch);
        $this->info     = (object)curl_getinfo($this->ch);
    }
}

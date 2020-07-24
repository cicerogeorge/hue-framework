<?php
namespace Application\Helpers;
/**
 * Class Http_Helper
 * 
 * Defines methods to deal with http requests
 * Requires curl library
 * 
 * @package application helpers
 * @author Cicero Monteiro <cicero@easy-step.no>
 */
class Http {

    /**
     * private $request
     *
     * @var object $request internal request object
     */
    private $request;

    /**
     * private $fields
     *
     * @var array $fields define the fields to be sent
     */
    private $fields = [];

    /**
     * private $url
     *
     * @var string $url defines the url to be accessed
     */
    private $url;

    /**
     * private $method
     *
     * @var string $method defines the method to be used(post, get, put or delete)
     */
    private $method;

    /**
     * private $contentType
     *
     * @var string $contentType defines the contentType to be used
     */
    private $contentType = 'application/json';

    /**
     * private $headers
     *
     * @var array $headers additional headers to be sent
     */
    private $headers = [];

    /**
     * private $auth (optional)
     *
     * @var array defines authorization username and password
     */
    private $auth = [];

    /**
     * private $json
     *
     * @var boolean defines if it's a json call
     */
    private $json = false;

    /**
     * __construct() defines the url
     *
     * @param string $url the url to be accessed
     * @return void
     */
    function __construct($url) {
        $this->setUrl($url);
    }

    /**
     * setUrl() defines which url will be used
     *
     * @param string $method the url to be called
     * @return void
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * setMethod() defines which http method will be used
     *
     * @param string $method the method to be used (post, get, put or delete)
     * @return void
     */
    public function setMethod($method) {
        $this->method = $method;
    }

    /**
     * setContentType() defines the content type header to be sent
     *
     * @param string $contentType the content type to be specified (application/json, application/xml, etc)
     * @return void
     */
    public function setContentType($contentType) {
        $this->contentType = 'Content-type: ' . $contentType;
        if ($contentType == 'application/json') {
            $this->json = true;
        }
    }

    /**
     * setHeaders() defines additional headers to be sent
     * 
     * @param array $headers the headers to be sent
     * @return void
     */
    public function setHeaders($headers) {
        $this->headers = $headers;
    }

    /**
     * setFields() defines fields to be posted on the http call
     *
     * @param array $fields
     * @return void
     */
    public function setFields($fields) {
        $this->fields = $fields;
    }

    /**
     * setAuthentication() defines authentication header parameters
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setAuthentication($username, $password) {
        $this->auth = [$username, $password];
    }

    /**
     * run() executes the http call
     *
     * @return string returns the call transfer string result
     */
    public function run() {
        // starts curl call
        $this->request = curl_init($this->url);
        $this->headers[] = $this->contentType;
        // sets json schema
        if ($this->json == true) {
            $this->fields = json_encode($this->fields);
        }
        // define default rules for curl call
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($this->request, CURLOPT_HEADER, 1);
        curl_setopt($this->request, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->request, CURLOPT_POSTFIELDS, $this->fields);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        // set authentication headers
        if (count($this->auth) > 0) {
            curl_setopt($this->request, CURLOPT_USERPWD, $this->auth[0] . ":" . $this->auth[1]);
        }
        // define http method
        switch (strtolower($this->method)) {
            case 'get':
                curl_setopt($this->request, CURLOPT_GET, true);
                break;
            case 'post':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            case 'post':
                curl_setopt($this->request, CURLOPT_PUT, true);
                break;
            case 'delete':
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
                break;
        }
        // execute call
        $return = curl_exec($this->request);
        // close call
        curl_close($this->request);

        return $return;
    }

}
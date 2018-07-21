<?php

namespace Submtd\Crawler;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Crawl
{
    /**
     * $urls
     * stores crawl information for each url
     *
     * @var array
     */
    protected $urls = [];

    /**
     * class constructor
     *
     * @param string $url
     */
    public function __construct($url = null)
    {
        if ($url) {
            $this->setUrl($url);
        }
    }

    /**
     * named constructor
     *
     * @param string $url
     * @return object
     */
    public static function url($url)
    {
        return new static($url);
    }

    /**
     * set the active url
     *
     * @param string $url
     * @return bool
     */
    public function setUrl($url)
    {
        $this->addUrl($url);
        reset($this->urls);
        while (key($this->urls) != $url) {
            if (!next($this->urls)) {
                throw new \Exception('Unable to set url.', 500);
            }
        }
        return true;
    }

    /**
     * add a url
     *
     * @param string $url
     * @return bool
     */
    public function addUrl($url)
    {
        $this->validateUrl($url);
        $host = $this->parseHost($url);
        $path = $this->parsePath($url);
        $url = $host . $path;
        // add the url to the urls array
        if (!isset($this->urls[$url])) {
            $this->urls[$url] = [
                'host' => $host,
                'path' => $path,
                'url' => $url,
                'statusCode' => null,
                'contentType' => null,
                'body' => null,
                'links' => [],
                'location' => null,
                'visited' => false,
                'error' => null,
            ];
        }
        return true;
    }

    /**
     * validate url
     *
     * @param string $url
     * @return bool
     */
    private function validateUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('The url format is invalid.', 400);
        }
        $url = parse_url($url);
        if (!in_array($url['scheme'], ['http', 'https'])) {
            throw new \Exception('Only http and https protocols are supported.', 400);
        }
    }

    /**
     * parse the host from a url
     *
     * @param string $url
     * @return string
     */
    private function parseHost($url)
    {
        $url = parse_url($url);
        $host = $url['scheme'] . '://';
        if (isset($url['user'])) {
            $host .= $url['user'];
            if (isset($url['pass'])) {
                $host .= ':' . $url['pass'];
            }
            $host .= '@';
        }
        $host .= $url['host'];
        if (isset($url['port'])) {
            $host .= ':' . $url['port'];
        }
        return $host;
    }

    /**
     * parse the path from a url
     *
     * @param string $url
     * @return string
     */
    private function parsePath($url)
    {
        $url = parse_url($url);
        $path = isset($url['path']) ? $url['path'] : '';
        if (isset($url['query'])) {
            $path .= $url['query'];
        }
        if (isset($url['fragment'])) {
            $path .= '#' . $url['fragment'];
        }
        return $path;
    }

    /**
     * retrieve a parameter from the active url
     *
     * @param string $parameter
     * @return mixed
     */
    private function getParameter($parameter)
    {
        return isset($this->urls[key($this->urls)][$parameter]) ? $this->urls[key($this->urls)][$parameter] : null;
    }

    /**
     * set a parameter for the active url
     *
     * @param string $parameter
     * @param mixed $value
     * @return void
     */
    private function setParameter($parameter, $value)
    {
        $this->urls[key($this->urls)][$parameter] = $value;
    }

    /**
     * return the urls property
     *
     * @return array
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * get the active url
     *
     * @return array
     */
    public function getActiveUrl()
    {
        return current($this->urls);
    }

    /**
     * make the next url active
     *
     * @return array
     */
    public function nextUrl()
    {
        if (!next($this->urls)) {
            reset($this->urls);
        }
        return $this->getActiveUrl();
    }

    /**
     * make the previous url active
     *
     * @return array
     */
    public function previousUrl()
    {
        if (!prev($this->urls)) {
            end($this->urls);
        }
        return $this->getActiveUrl();
    }

    /**
     * get the current host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->getParameter('host');
    }

    /**
     * get the current path
     *
     * @return path
     */
    public function getPath()
    {
        return $this->getParameter('path');
    }

    /**
     * get the current url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getParameter('url');
    }

    /**
     * get the current status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->getParameter('statusCode');
    }

    /**
     * set the current status code
     *
     * @param int $code
     * @return void
     */
    private function setStatusCode($code)
    {
        $this->setParameter('statusCode', $code);
    }

    /**
     * get the current content type header
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getParameter('contentType');
    }

    /**
     * set the current content type
     *
     * @param string $type
     * @return void
     */
    private function setContentType($type)
    {
        $this->setParameter('contentType', $type);
    }

    /**
     * get the current body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getParameter('body');
    }

    /**
     * set the current body
     *
     * @param string $body
     * @return void
     */
    private function setBody($body)
    {
        $this->setParameter('body', $body);
    }

    /**
     * get the current location header value
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->getParameter('location');
    }

    /**
     * set the current location
     *
     * @param string $location
     * @return void
     */
    public function setLocation($location = null)
    {
        $this->setParameter('location', $location);
        $this->addUrl($location);
    }

    /**
     * return the links on the current page
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->getParameter('links');
    }

    /**
     * add a link to the links array
     *
     * @param string $anchorText
     * @param string $url
     * @return void
     */
    private function addLink($anchorText, $url)
    {
        $host = $this->parseHost($url);
        $path = $this->parsePath($url);
        $url = $host . $path;
        $isInternal = $host == $this->getHost();
        if (!isset($this->getActiveUrl()['links'][$url])) {
            $this->urls[key($this->urls)]['links'][$url] = [
                'anchorText' => $anchorText,
                'url' => $url,
                'isInternal' => $isInternal,
            ];
        }
        if ($isInternal) {
            $this->addUrl($url);
        }
    }

    /**
     * have you visited the site yet?
     *
     * @return bool
     */
    public function visited()
    {
        return $this->getParameter('visited');
    }

    /**
     * mark the current url as visited
     *
     * @return void
     */
    private function markAsVisited()
    {
        $this->setParameter('visited', true);
    }

    /**
     * return the error parameter for the current url
     *
     * @return string
     */
    public function getError()
    {
        return $this->getParameter('error');
    }

    /**
     * set the error for the current url
     *
     * @param string $error
     * @return void
     */
    private function setError($error)
    {
        $this->setParameter('error', $error);
    }

    /**
     * crawl the current url
     *
     * @param string $url
     * @return void
     */
    public function fetch($url = null)
    {
        if ($url) {
            $this->setUrl($url);
        }
        if ($this->visited()) {
            return;
        }
        try {
            $client = new Client();
            $request = $client->request('GET', $this->getUrl(), ['allow_redirects' => false]);
            $this->setStatusCode($request->getStatusCode());
            $this->setError($request->getReasonPhrase());
            $this->setContentType($request->getHeaderLine('Content-Type'));
            $this->setBody($request->getBody()->getContents());
            $this->markAsVisited();
            $this->extractLinks();
            $this->setLocation($request->getHeaderLine('Location'));
        } catch (\Exception $e) {
            $this->setStatusCode($e->getCode());
            $this->setError($e->getMessage());
        }
    }

    /**
     * extract links from the current url
     *
     * @return void
     */
    private function extractLinks()
    {
        $crawler = $this->getCrawler();
        $links = $crawler->filter('a')->links();
        foreach ($links as $link) {
            $this->addLink($link->getNode()->textContent, $link->getUri());
        }
    }

    /**
     * return an instance of Symfony DomCrawler
     *
     * @return object
     */
    private function getCrawler()
    {
        return new Crawler($this->getBody(), $this->getUrl());
    }
}

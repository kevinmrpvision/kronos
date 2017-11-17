<?php

/**
 * Copyright (c) 2016, 2017 François Kooman <fkooman@tuxed.net>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Mrpvision\Kronos;

class Provider
{
    /** @var string */
    private $base_uri;
    
    /** @var string */
    private $username;

    /** @var string|null */
    private $password;
    
    /** @var string|null */
    private $api_key;
    
    /** @var string|null */
    private $companyID;
    
    /** @var string|null */
    private $companyShortName;

    /** @var string */
    private $login;

    /** @var string */
    private $tokenEndpoint;

    /**
     * @param string      $clientId
     * @param string|null $clientSecret
     * @param string      $authorizationEndpoint
     * @param string      $tokenEndpoint
     */
    public function __construct($base_uri,$username, $password,$api_key,$companyID,$companyShortName, $login, $token_referesh)
    {
        $this->base_uri = $base_uri;
        $this->username = $username;
        $this->password = $password;
        $this->api_key = $api_key;
        $this->companyID = $companyID;
        $this->companyShortName = $companyShortName;
        $this->login = $login;
        $this->tokenEndpoint = $token_referesh;
    }

    /**
     * @return string
     */
    public function getProviderId()
    {
        return $this->username;
    }
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->username;
    }

    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-2.2
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string|null
     *
     * @see https://tools.ietf.org/html/rfc6749#section-2.3.1
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.1
     */
    public function getLoginEndpoint()
    {
        return $this->base_uri.$this->login;
    }

    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.2
     */
    public function getTokenEndpoint()
    {
        return $this->base_uri.$this->tokenEndpoint;
    }
    
    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.2
     */
    public function getCompanyShortName()
    {
        return $this->companyShortName;
    }
    
    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.2
     */
    public function getCompanyID()
    {
        return $this->companyID;
    }
    
    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-3.2
     */
    public function getFullURL($url) {
        if (!isset(parse_url($url)['host'])) {
            $url = $this->base_uri . $url;
        }
        $find = ['{cid}', '{cname}'];
        $replace = [$this->companyID, $this->companyShortName];
        return str_replace($find, $replace, $url);
    }

}

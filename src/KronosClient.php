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

use DateTime;
use Mrpvision\Kronos\Exception\OAuthException;
use Mrpvision\Kronos\Exception\OAuthServerException;
use Mrpvision\Kronos\Http\HttpClientInterface;
use Mrpvision\Kronos\Http\Request;
use Mrpvision\Kronos\Http\Response;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\ConstantTime\Base64UrlSafe;

class KronosClient
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var \Mrpvision\Kronos\Http\HttpClientInterface */
    private $httpClient;

    /** @var SessionInterface */
    private $session;

    /** @var RandomInterface */
    private $random;

    /** @var \DateTime */
    private $dateTime;

    /** @var Provider */
    private $provider = null;

    /** @var string */
    private $userId = null;

    /**
     * @param TokenStorageInterface    $tokenStorage
     * @param Http\HttpClientInterface $httpClient
     * @param SessionInterface|null    $session
     * @param RandomInterface|null     $random
     * @param \DateTime|null           $dateTime
     */
    public function __construct(
            
        TokenStorageInterface $tokenStorage,
        HttpClientInterface $httpClient,
        SessionInterface $session = null,
        RandomInterface $random = null,
        DateTime $dateTime = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->httpClient = $httpClient;
        if (null === $session) {
            $session = new Session();
        }
        $this->session = $session;
        if (null === $random) {
            $random = new Random();
        }
        $this->random = $random;
        if (null === $dateTime) {
            $dateTime = new DateTime();
        }
        $this->dateTime = $dateTime;
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return void
     */
    public function setDateTime(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param Provider $provider
     *
     * @return void
     */
    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
    }
    
    /**
     * @param Provider $provider
     *
     * @return void
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Perform a GET request, convenience wrapper for ::send().
     *
     * @param string $requestScope
     * @param string $requestUri
     * @param array  $requestHeaders
     *
     * @return Http\Response|false
     */
    public function get($requestUri, array $requestHeaders = [])
    {
        $requestUri = $this->provider->getFullURL($requestUri);
        return $this->send(Request::get($requestUri, $requestHeaders));
    }

    /**
     * Perform a POST request, convenience wrapper for ::send().
     *
     * @param string $requestScope
     * @param string $requestUri
     * @param array  $postBody
     * @param array  $requestHeaders
     *
     * @return Http\Response|false
     */
    public function post($requestUri, array $postBody, array $requestHeaders = [])
    {
        $requestUri = $this->provider->getFullURL($requestUri);
        return $this->send( Request::post($requestUri, $postBody, $requestHeaders));
    }
    /**
     * Perform a PUT request, convenience wrapper for ::send().
     *
     * @param string $requestScope
     * @param string $requestUri
     * @param array  $postBody
     * @param array  $requestHeaders
     *
     * @return Http\Response|false
     */
    public function rawpost($requestUri, array $postBody, array $requestHeaders = [])
    {
        $requestUri = $this->provider->getFullURL($requestUri);
        return $this->send( Request::rawpost($requestUri, $postBody, $requestHeaders));
    }
    /**
     * Perform a PUT request, convenience wrapper for ::send().
     *
     * @param string $requestScope
     * @param string $requestUri
     * @param array  $postBody
     * @param array  $requestHeaders
     *
     * @return Http\Response|false
     */
    public function put($requestUri, array $postBody, array $requestHeaders = [])
    {
        $requestUri = $this->provider->getFullURL($requestUri);
        return $this->send( Request::put($requestUri, $postBody, $requestHeaders));
    }
    /**
     * Perform a DELETE request, convenience wrapper for ::send().
     *
     * @param string $requestScope
     * @param string $requestUri
     * @param array  $postBody
     * @param array  $requestHeaders
     *
     * @return Http\Response|false
     */
    public function delete($requestUri, array $requestHeaders = [])
    {
        $requestUri = $this->provider->getFullURL($requestUri);
        return $this->send( Request::delete($requestUri, $requestHeaders));
    }

    /**
     * Perform a HTTP request.
     *
     * @param string       $requestScope
     * @param Http\Request $request
     *
     * @return Response|false
     */
    public function send(Request $request)
    {
        if (null === $this->userId) {
            throw new OAuthException('userId not set');
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        if ($accessToken->isExpired($this->dateTime)) {
            // try to refresh the AccessToken
            $accessToken = $this->refreshAccessToken($accessToken);
            if (!$accessToken) {
                // didn't work
                return false;
            }
        }

        // add Authorization header to the request headers
        $request->setHeader('Authentication', sprintf('Bearer %s', $accessToken->getToken()));
        $request->setHeader('Api-key', $this->provider->getApiKey());

        $response = $this->httpClient->send($request);
        if (401 === $response->getStatusCode()) {
            // the access_token was not accepted, but isn't expired, we assume
            // the user revoked it, also no need to try with refresh_token
            $this->tokenStorage->deleteAccessToken($this->userId, $accessToken);

            return false;
        }

        return $response;
    }

   

    /**
     * @param AccessToken $accessToken
     *
     * @return AccessToken|false
     */
    private function refreshAccessToken(AccessToken $accessToken)
    {
        $requestHeaders = [
                'Api-Key' =>  $this->provider->getApiKey(),
                'Accept'=>'application/json',
                'Api-key'=>$this->provider->getApiKey(),
                'Authentication' => sprintf('Bearer %s', $accessToken->getToken())
            ];
        
        $response = $this->httpClient->send(
            Request::get(
                $this->provider->getTokenEndpoint(),
                $requestHeaders
            )
        );
        if (401 === $response->getStatusCode()) {
            // the access_token was not accepted, but isn't expired, we assume
            // the user revoked it, also no need to try with refresh_token
            $this->tokenStorage->deleteAccessToken($this->userId, $accessToken);
            return $this->getAccessToken();
        }
        if (!$response->isOkay()) {
            // if there is any other error, we can't deal with this here...
            throw new OAuthServerException($response);
        }
        $accessToken = AccessToken::fromCodeResponse(
            $this->provider,
            $this->dateTime,
            $response->json(),
            $response
        );
        $this->tokenStorage->storeAccessToken($this->userId, $accessToken);
        return $accessToken;
    }

    /**
     * Find an AccessToken in the list that matches this scope, bound to
     * providerId and userId.
     *
     * @param string $scope
     *
     * @return AccessToken|false
     */
    private function getAccessToken()
    {
        $accessTokenList = $this->tokenStorage->getAccessTokenList($this->userId);
        foreach ($accessTokenList as $accessToken) {
//            $this->tokenStorage->deleteAccessToken($this->userId, $accessToken);
//            dd($accessToken);
            return $accessToken;
        }
        $tokenRequestData = [
            'credentials' => [
                'username' => $this->provider->getUserName(),
                'password' => $this->provider->getPassword(),
                'company' => $this->provider->getCompanyShortName()
            ]
        ];
        $requestHeaders = [
                'Api-Key' =>  $this->provider->getApiKey(),
                'Accept'=>'application/json',
                'Content-Type' => 'application/json',
            ];
        
        $response = $this->httpClient->send(
            Request::rawpost(
                $this->provider->getLoginEndpoint(),
                $tokenRequestData,
                $requestHeaders
            )
        );
        if (!$response->isOkay()) {
            // if there is any other error, we can't deal with this here...
            throw new OAuthServerException($response);
        }
        $accessToken = AccessToken::fromCodeResponse(
            $this->provider,
            $this->dateTime,
            $response->json(),
            $response
        );
        $this->tokenStorage->storeAccessToken($this->userId, $accessToken);
        return $accessToken;
    }

}

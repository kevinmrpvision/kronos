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

namespace mrpvision\kronos;

use DateInterval;
use DateTime;
use mrpvision\kronos\Exception\AccessTokenException;
use RuntimeException;

class AccessToken
{
    /** @var string */
    private $username;

    /** @var \DateTime */
    private $issuedAt;

    /** @var string */
    private $accessToken;


    /** @var int|null */
    private $expiresIn = null;

    /** @var string|null */
    private $refreshToken = null;

    /** @var string|null */
    private $scope = null;

    /**
     * @param array $tokenData
     */
    public function __construct(array $tokenData)
    {
        $requiredKeys = [ 'token'];
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $tokenData)) {
                throw new AccessTokenException(sprintf('missing key "%s"', $requiredKey));
            }
        }

        $this->setIssuedAt($tokenData['issued_at']);
        $this->setToken($tokenData['token']);

        // set optional keys
        if (array_key_exists('expires_in', $tokenData)) {
            $this->setExpiresIn($tokenData['expires_in']);
        }
        
    }

    /**
     * @param Provider  $provider
     * @param \DateTime $dateTime
     * @param array     $tokenData
     * @param string    $scope
     *
     * @return AccessToken
     */
    public static function fromCodeResponse(Provider $provider, DateTime $dateTime, array $tokenData)
    {
        $tokenData['username'] = $provider->getUsername();

        // if the scope was not part of the response, add the request scope,
        // because according to the RFC, if the scope is ommitted the requested
        // scope was granted!
        
        // add the current DateTime as well to be able to figure out if the
        // token expired
        $tokenData['issued_at'] = $dateTime->format('Y-m-d H:i:s');
        $tokenData['expires_in'] = $tokenData['ttl'];
        return new self($tokenData);
    }

    /**
     * @param Provider    $provider
     * @param \DateTime   $dateTime
     * @param array       $tokenData
     * @param AccessToken $accessToken to steal the old scope and refresh_token from!
     *
     * @return AccessToken
     */
    public static function fromRefreshResponse(Provider $provider, DateTime $dateTime, array $tokenData, AccessToken $accessToken)
    {
        $tokenData['username'] = $provider->getUsername();

        
        // if the refresh_token is not part of the response, we wil reuse the
        // existing refresh_token for future refresh_token requests
        if (!array_key_exists('refresh_token', $tokenData)) {
            $tokenData['refresh_token'] = $accessToken->getRefreshToken();
        }
        // add the current DateTime as well to be able to figure out if the
        // token expired
        $tokenData['issued_at'] = $dateTime->format('Y-m-d H:i:s');

        return new self($tokenData);
    }

    /**
     * @return \DateTime
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * @return \DateTime
     */
    public function getIssuedAt()
    {
        return $this->issuedAt;
    }

    /**
     * @return string
     *
     * @see https://tools.ietf.org/html/rfc6749#section-5.1
     */
    public function getToken()
    {
        return $this->accessToken;
    }

   

    /**
     * @return int|null
     *
     * @see https://tools.ietf.org/html/rfc6749#section-5.1
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return bool
     */
    public function isExpired(DateTime $dateTime)
    {
        if (null === $this->getExpiresIn()) {
            // if no expiry was indicated, assume it is valid
            return false;
        }

        // check to see if issuedAt + expiresIn > provided DateTime
        $expiresAt = clone $this->issuedAt;
        $expiresAt->add(new DateInterval(sprintf('PT%dS', $this->getExpiresIn())));

        return $dateTime >= $expiresAt;
    }

    /**
     * @param string $jsonString
     *
     * @return AccessToken
     */
    public static function fromJson($jsonString)
    {
        $tokenData = json_decode($jsonString, true);
        if (null === $tokenData && JSON_ERROR_NONE !== json_last_error()) {
            $errorMsg = function_exists('json_last_error_msg') ? json_last_error_msg() : json_last_error();
            throw new AccessTokenException(sprintf('unable to decode JSON from storage: %s', $errorMsg));
        }

        return new self($tokenData);
    }

    /**
     * @return string
     */
    public function toJson()
    {
        $jsonData = [
                'issued_at' => $this->issuedAt->format('Y-m-d H:i:s'),
                'access_token' => $this->getToken(),
                'expires_in' => $this->getExpiresIn(),
        ];

        if (false === $jsonString = json_encode($jsonData)) {
            throw new RuntimeException('unable to encode JSON');
        }

        return $jsonString;
    }


    /**
     * @param string $issuedAt
     *
     * @return void
     */
    private function setIssuedAt($issuedAt)
    {
        self::requireString('expires_at', $issuedAt);
        if (1 !== preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $issuedAt)) {
            throw new AccessTokenException('invalid "expires_at"');
        }
        $this->issuedAt = new DateTime($issuedAt);
    }

    /**
     * @param string $accessToken
     *
     * @return void
     */
    private function setToken($accessToken)
    {
        self::requireString('token', $accessToken);
        // access-token = 1*VSCHAR
        // VSCHAR       = %x20-7E
        if (1 !== preg_match('/^[\x20-\x7E]+$/', $accessToken)) {
            throw new AccessTokenException('invalid "token"');
        }
        $this->accessToken = $accessToken;
    }

    /**
     * @param int|null $expiresIn
     *
     * @return void
     */
    private function setExpiresIn($expiresIn)
    {
        if (null !== $expiresIn) {
            self::requireInt('expires_in', $expiresIn);
            if (0 >= $expiresIn) {
                throw new AccessTokenException('invalid "expires_in"');
            }
        }
        $this->expiresIn = $expiresIn;
    }

    /**
     * @param string $k
     * @param string $v
     *
     * @return void
     */
    private static function requireString($k, $v)
    {
        if (!is_string($v)) {
            throw new AccessTokenException(sprintf('"%s" must be string', $k));
        }
    }

    /**
     * @param string $k
     * @param int    $v
     *
     * @return void
     */
    private static function requireInt($k, $v)
    {
        if (!is_int($v)) {
            throw new AccessTokenException(sprintf('"%s" must be int', $k));
        }
    }
}

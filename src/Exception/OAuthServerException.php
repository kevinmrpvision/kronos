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

namespace mrpvision\kronos\Exception;

use Exception;
use mrpvision\kronos\Http\Response;

class OAuthServerException extends OAuthException
{
    /**
     * @param int $code
     */
    public function __construct(Response $response, $code = 0, Exception $previous = null)
    {
        $responseData = $response->json();
        $statusCode = $response->getStatusCode();

        $errorMsg = sprintf('[%d] error', $statusCode);
        if (array_key_exists('error', $responseData)) {
            $errorMsg = sprintf('[%d] %s', $statusCode, $responseData['error']);
            if (array_key_exists('error_description', $responseData)) {
                $errorMsg = sprintf('[%d] %s (%s)', $statusCode, $responseData['error'], $responseData['error_description']);
            }
        }

        parent::__construct($errorMsg, $code, $previous);
    }
}

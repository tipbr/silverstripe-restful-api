<?php

namespace FullscreenInteractive\Restful\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;

/**
 * Middleware to handle JSON request/response processing
 */
class JsonMiddleware implements HTTPMiddleware
{
    /**
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        // Parse JSON input if content-type is application/json
        $contentType = (string) $request->getHeader('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $jsonPayload = trim(file_get_contents("php://input"));

            if ($jsonPayload) {
                $input = json_decode($jsonPayload, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Return JSON error for invalid JSON
                    $response = HTTPResponse::create();
                    $response->setStatusCode(400);
                    $response->addHeader('Content-Type', 'application/json');
                    $response->setBody(json_encode([
                        'success' => false,
                        'timestamp' => time(),
                        'error' => [
                            'message' => 'Invalid JSON: ' . json_last_error_msg(),
                            'code' => 400
                        ]
                    ]));

                    return $response;
                }

                // Merge JSON input with request vars
                if ($input) {
                    $request->setBody($jsonPayload);
                }
            }
        }

        // Process the request
        $response = $delegate($request);

        // Ensure JSON content-type header on response
        if (!$response->getHeader('Content-Type')) {
            $response->addHeader('Content-Type', 'application/json');
        }

        return $response;
    }
}

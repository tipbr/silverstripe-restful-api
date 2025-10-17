<?php

namespace TipBr\RestfulApi\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;

/**
 * Middleware to handle CORS requests
 */
class CorsMiddleware implements HTTPMiddleware
{
    /**
     * List of allowed origins for CORS. Empty array = allow all (dev only)
     *
     * @config
     * @var array
     */
    private static $allowed_origins = [];

    /**
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        // Handle OPTIONS preflight request
        if ($request->httpMethod() === 'OPTIONS') {
            $response = HTTPResponse::create('', 200);
            $this->addCorsHeaders($response, $request);

            if ($request->getHeader('Access-Control-Request-Method')) {
                $response->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            }

            if ($request->getHeader('Access-Control-Request-Headers')) {
                $response->addHeader(
                    'Access-Control-Allow-Headers',
                    $request->getHeader('Access-Control-Request-Headers')
                );
            }

            return $response;
        }

        // Process normal request
        $response = $delegate($request);

        // Add CORS headers to response
        $this->addCorsHeaders($response, $request);

        return $response;
    }

    /**
     * Add CORS headers to response
     *
     * @param HTTPResponse $response
     * @param HTTPRequest $request
     */
    protected function addCorsHeaders(HTTPResponse $response, HTTPRequest $request): void
    {
        $allowedOrigins = $this->config()->get('allowed_origins');
        $origin = $request->getHeader('Origin');

        if (!empty($allowedOrigins) && is_array($allowedOrigins)) {
            if (in_array($origin, $allowedOrigins)) {
                $response->addHeader('Access-Control-Allow-Origin', $origin);
                $response->addHeader('Access-Control-Allow-Credentials', 'true');
            }
        } else {
            // Fallback for development (NOT for production!)
            $response->addHeader('Access-Control-Allow-Origin', '*');
        }

        $response->addHeader('Access-Control-Max-Age', '86400'); // 24 hours
    }
}

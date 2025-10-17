<?php

declare(strict_types=1);

namespace TipBr\RestfulApi\Controllers;

use TipBr\RestfulApi\Traits\InputValidation;
use TipBr\RestfulApi\Traits\JsonResponse;
use TipBr\RestfulApi\Traits\JwtAuthentication;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;

class ApiController extends Controller
{
    use JwtAuthentication;
    use JsonResponse;
    use InputValidation;

    public function init()
    {
        parent::init();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $response = $this->getResponse()
                ->addHeader('Access-Control-Allow-Origin', '*')
                ->addHeader("Content-type", "application/json");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                $response = $response->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                $response = $response->addHeader(
                    'Access-Control-Allow-Headers',
                    $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']
                );
            }

            $response->output();
            exit;
        }

        $contentType = (string) $this->request->getHeader('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $jsonPayload = trim(file_get_contents("php://input"));
            $input = json_decode($jsonPayload, true);

            if ($input) {
                $this->vars = array_merge($input, $this->request->getVars());
            } elseif ($jsonPayload) {
                $error = json_last_error();

                switch ($error) {
                    case JSON_ERROR_NONE:
                        $this->vars = $this->request->getVars();
                        break;
                    default:
                        $this->failure([
                            'error' => 'Invalid JSON',
                            'code' => $error
                        ]);
                        break;
                }
            } else {
                $this->vars = $this->request->requestVars();
            }
        } else {
            $this->vars = $this->request->requestVars();
        }

        if ($this->vars) {
            $this->vars = array_change_key_case($this->vars);
        }

        $this->getResponse()
            ->addHeader('Access-Control-Allow-Origin', '*')
            ->addHeader("Content-type", "application/json");
    }


    public function index()
    {
        return $this->httpError(400, 'Bad Request');
    }

    /**
     * Standardized HTTP error response
     *
     * @param int $errorCode
     * @param string $errorMessage
     * @throws HTTPResponse_Exception
     */
    public function httpError($errorCode = 404, $errorMessage = '')
    {
        if (!$errorMessage) {
            switch ($errorCode) {
                case 404:
                    $errorMessage = 'Missing resource';
                    break;
                case 400:
                    $errorMessage = 'Bad Request';
                    break;
                case 401:
                    $errorMessage = 'Unauthorized';
                    break;
                case 403:
                    $errorMessage = 'Forbidden';
                    break;
                default:
                    $errorMessage = 'An error occurred';
                    break;
            }
        }

        // Standardized error format
        $body = json_encode([
            'success' => false,
            'timestamp' => time(),
            'error' => [
                'message' => $errorMessage,
                'code' => $errorCode
            ]
        ]);

        $response = HTTPResponse::create(
            $body,
            $errorCode
        );

        $response->addHeader("Content-type", "application/json");
        $response->addHeader('Access-Control-Allow-Origin', '*');

        $err = new HTTPResponse_Exception();
        $err->setResponse($response);

        throw $err;
    }

    /**
     * @throws HTTPResponse_Exception
     */
    public function ensureGET()
    {
        if (!$this->request->isGet()) {
            $this->httpError(400, 'Request must be provided as a GET request');
        }
    }

    /**
     * @throws HTTPResponse_Exception
     */
    public function ensurePOST()
    {
        if (!$this->request->isPost()) {
            $this->httpError(400, 'Request must be provided as a POST request');
        }
    }

    /**
     * @throws HTTPResponse_Exception
     */
    public function ensureDelete(): void
    {
        if ($this->request->isDELETE()) {
            return;
        }

        $this->httpError(400, 'Request must be provided as a DELETE request');
    }
}
    }
}

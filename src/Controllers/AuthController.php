<?php

namespace FullscreenInteractive\Restful\Controllers;

use FullscreenInteractive\Restful\JWT\JWTUtils;
use FullscreenInteractive\Restful\JWT\JWTUtilsException;
use FullscreenInteractive\Restful\Models\RefreshToken;
use SilverStripe\Model\ArrayData;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class AuthController extends ApiController
{
    private static $allowed_actions = [
        'token',
        'verify',
        'refresh',
    ];

    /**
     * The token is acquired by using basic auth. Once the user has entered the
     * username / password and completed this first step then we give them back
     * a token which contains their information and a refresh token
     */
    public function token()
    {
        try {
            $payload = JWTUtils::inst()->byBasicAuth($this->request, true);

            if (isset($payload['member']['id'])) {
                $member = Member::get()->byID($payload['member']['id']);

                if ($member) {
                    $api = [];

                    if ($member->hasMethod('toApi')) {
                        $api = $member->toApi() ?? [];

                        if ($api instanceof ArrayData) {
                            $api = $api->toMap();
                        }
                    }

                    $payload['member'] = array_merge($payload['member'], $api);

                    // Generate refresh token
                    $refreshToken = RefreshToken::generate($member);
                    $payload['refreshToken'] = $refreshToken->Token;
                }

                return $this->returnArray($payload);
            }

            return $this->returnArray($payload);
        } catch (JWTUtilsException $e) {
            return $this->httpError(403, $e->getMessage());
        }
    }


    /**
     * Verifies a token is valid
     */
    public function verify()
    {
        if ($jwt = $this->getJwt()) {
            $verifyResponse = [
                'token' => $jwt
            ];

            $this->invokeWithExtensions('onVerify', $verifyResponse);

            return $this->returnArray($verifyResponse);
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refresh()
    {
        $this->ensurePOST();

        $refreshTokenValue = $this->getVar('refreshToken');

        if (!$refreshTokenValue) {
            return $this->httpError(400, 'Missing refresh token');
        }

        $refreshToken = RefreshToken::findValid($refreshTokenValue);

        if (!$refreshToken) {
            return $this->httpError(401, 'Invalid or expired refresh token');
        }

        $member = $refreshToken->Member();

        if (!$member || !$member->exists()) {
            return $this->httpError(401, 'Invalid member');
        }

        // Generate new access token
        $payload = JWTUtils::inst()->forMember($member, true);

        if ($member->hasMethod('toApi')) {
            $api = $member->toApi() ?? [];

            if ($api instanceof ArrayData) {
                $api = $api->toMap();
            }

            $payload['member'] = array_merge($payload['member'], $api);
        }

        // Keep the same refresh token
        $payload['refreshToken'] = $refreshTokenValue;

        return $this->returnArray($payload);
    }
}

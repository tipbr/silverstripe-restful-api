<?php

namespace TipBr\RestfulApi\Traits;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use TipBr\RestfulApi\JWT\JWTUtils;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

trait JwtAuthentication
{
    /**
     * If this endpoint requires authorization then we want to get the member
     * for the operation. Uses UUID to identify member instead of internal ID.
     *
     * @param array|null $permissionCodes
     * @return Member
     * @throws HTTPResponse_Exception
     */
    protected function ensureUserLoggedIn(?array $permissionCodes = null): Member
    {
        $token = JWT::decode(
            $this->getJwt(),
            new Key(
                Config::inst()->get(JWTUtils::class, 'secret'),
                'HS256'
            )
        );

        // Use UUID instead of ID
        $member = null;
        if (isset($token->memberUuid)) {
            $member = Member::get()->filter('UUID', $token->memberUuid)->first();
        } elseif (isset($token->memberId)) {
            // Fallback for backwards compatibility
            $member = Member::get()->byID($token->memberId);
        }

        if ($member) {
            if ($permissionCodes) {
                if (!Permission::checkMember($member, $permissionCodes)) {
                    return $this->httpError(401);
                }
            }

            Injector::inst()->get(IdentityStore::class)->logIn($member);
            Security::setCurrentUser($member);

            return $member;
        } else {
            return $this->httpError(401);
        }
    }

    /**
     * Returns the JWT token from the Authorization header.
     *
     * @return string
     * @throws HTTPResponse_Exception
     */
    protected function getJwt(): string
    {
        $bearer = $this->getBearerToken();

        if (!$bearer) {
            return $this->httpError(401);
        }

        if (!JWTUtils::inst()->check($bearer)) {
            return $this->httpError(401);
        }

        $token = JWT::decode(
            $bearer,
            new Key(
                Config::inst()->get(JWTUtils::class, 'secret'),
                'HS256'
            )
        );

        $jwt = JWTUtils::inst()->renew($bearer);

        if (!$jwt) {
            return $this->httpError(401);
        }

        // Set the current user using UUID
        $member = null;
        if (isset($token->memberUuid)) {
            $member = Member::get()->filter('UUID', $token->memberUuid)->first();
        } elseif (isset($token->memberId)) {
            // Fallback for backwards compatibility
            $member = Member::get()->byID($token->memberId);
        }

        if ($member) {
            Injector::inst()->get(IdentityStore::class)->logIn($member);
            Security::setCurrentUser($member);
        }

        return $jwt;
    }

    /**
     * Get Authorization header from request
     *
     * @return string
     */
    protected function getAuthorizationHeader(): string
    {
        $header = '';

        if ($auth = $this->getRequest()->getHeader('Authorization')) {
            $header = trim($auth);
        } elseif ($auth = $this->getRequest()->getHeader('HTTP_AUTHORIZATION')) {
            $header = trim($auth);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders['Authorization'])) {
                $header = trim($requestHeaders['Authorization']);
            }
        }

        return $header;
    }

    /**
     * Returns the bearer token value from the Authorization Header
     *
     * @return string
     */
    protected function getBearerToken(): string
    {
        $headers = $this->getAuthorizationHeader();

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }
}

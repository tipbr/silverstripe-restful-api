<?php

namespace TipBr\RestfulApi\JWT;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\BasicAuth;

/**
 * Utility Class for handling JWTs.
 */
class JWTUtils
{
    /**
     * @var int Config: JWT lifetime in days
     */
    private static $lifetime_in_days = 7;

    /**
     * @var int Config: Relevant for 'rat' claim (renewed at)
     */
    private static $renew_threshold_in_minutes = 60;

    /**
     * @var string Config: JWT secret for signing tokens
     */
    private static $secret = '';

    /**
     * @var string Config: Issuer claim
     */
    private static $iss = '';

    /**
     * @var JWTUtils Singleton instance holder
     */
    private static $instance = null;

    /**
     * @return JWTUtils
     * @throws JWTUtilsException
     */
    public static function inst()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Clears the singleton instance. Helps with PHPUnit testing.
     */
    public static function tearDown()
    {
        self::$instance = null;
    }

    /**
     * JWTUtils constructor.
     * @throws JWTUtilsException
     */
    private function __construct()
    {
        if (!$this->hasValidSecret()) {
            throw new JWTUtilsException('No "secret" config found.');
        }
    }

    /**
     * Disables the magic clone method
     */
    private function __clone()
    {
    }

    /**
     * Checks for a valid "secret" config
     *
     * @return bool
     */
    private function hasValidSecret()
    {
        return boolval(Injector::inst()->convertServiceProperty(Config::inst()->get(self::class, 'secret')));
    }

    /**
     * @return int Unix timestamp of token expiration
     */
    private function calcExpirationClaim()
    {
        return Carbon::now()->addDays(Config::inst()->get(self::class, 'lifetime_in_days'))->timestamp;
    }

    /**
     * Generates a fresh set of default claims.
     *
     * @return array
     */
    public function getClaims()
    {
        return [
            'iss' => Config::inst()->get(self::class, 'iss') ?: Director::absoluteBaseURL(),
            'exp' => $this->calcExpirationClaim(),
            'iat' => time(),
            'rat' => time(),
            'jti' => Uuid::uuid4()->toString()
        ];
    }

    /**
     * Creates a new token from Basic Auth member data
     *
     * @param HTTPRequest $request
     * @param bool $includeMemberData
     *
     * @return array
     * @throws JWTUtilsException
     */
    public function byBasicAuth($request, $includeMemberData = true)
    {
        // Try to authenticate member with basic auth
        try {
            $member = BasicAuth::requireLogin($request, null, null, false);
        } catch (HTTPResponse_Exception $e) {
            throw new JWTUtilsException($e->getResponse()->getBody());
        }

        return $this->forMember($member, $includeMemberData);
    }

    /**
     * Creates a new token for a given member
     *
     * @param \SilverStripe\Security\Member $member
     * @param bool $includeMemberData
     *
     * @return array
     */
    public function forMember($member, $includeMemberData = true)
    {
        // Create JWT with all claims using UUID instead of ID
        $claims = [
            'memberId' => $member->ID, // Keep for backwards compatibility
        ];

        // Use UUID if available
        if ($member->hasField('UUID') && $member->UUID) {
            $claims['memberUuid'] = $member->UUID;
        }

        $token = JWT::encode(
            array_merge($claims, $this->getClaims()),
            Config::inst()->get(self::class, 'secret'),
            'HS256'
        );

        $payload = [
            'token' => $token
        ];

        // Check if member data should be included
        if ($includeMemberData) {
            $memberData = [
                'email'     => $member->Email,
                'firstName' => $member->FirstName,
                'surname'   => $member->Surname
            ];

            // Use UUID as identifier if available
            if ($member->hasField('UUID') && $member->UUID) {
                $memberData['uuid'] = $member->UUID;
            } else {
                $memberData['id'] = $member->ID; // Fallback
            }

            $payload['member'] = $memberData;
        }

        return $payload;
    }

    /**
     * Checks if the given token is valid and needs to be renewed
     *
     * @param string $token The decoded token to renew
     *
     * @return string The renewed decoded token
     * @throws JWTUtilsException
     */
    public function renew($token)
    {
        try {
            $jwt = (array)JWT::decode(
                $token,
                new Key(
                    Config::inst()->get(self::class, 'secret'),
                    'HS256'
                )
            );
        } catch (\Exception $e) {
            throw new JWTUtilsException($e->getMessage());
        }

        // Check if token needs to be renewed
        $renewedAt = Carbon::createFromTimestamp($jwt['rat']);
        if (
            $renewedAt->diffInMinutes(Carbon::now()) <
            Config::inst()->get(self::class, 'renew_threshold_in_minutes')
        ) {
            // Token was refreshed less than threshold ago, return same token
            return $token;
        }

        // Update 'exp' and 'rat' claims
        $jwt['exp'] = $this->calcExpirationClaim();
        $jwt['rat'] = time();

        // Renew and return token
        return JWT::encode(
            $jwt,
            Config::inst()->get(self::class, 'secret'),
            'HS256'
        );
    }

    /**
     * Checks if token is valid and non-expired
     *
     * @param string $token
     *
     * @return bool
     */
    public function check($token)
    {
        try {
            JWT::decode(
                $token,
                new Key(
                    Config::inst()->get(self::class, 'secret'),
                    'HS256'
                )
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

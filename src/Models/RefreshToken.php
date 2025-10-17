<?php

namespace TipBr\RestfulApi\Models;

use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * Represents a refresh token for JWT authentication
 */
class RefreshToken extends DataObject
{
    private static $table_name = 'RefreshToken';

    private static $db = [
        'Token' => 'Varchar(255)',
        'ExpiresAt' => 'Datetime',
        'IsRevoked' => 'Boolean',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $indexes = [
        'Token' => true,
    ];

    private static $defaults = [
        'IsRevoked' => false,
    ];

    /**
     * Generate a new refresh token
     *
     * @param Member $member
     * @param int $daysValid Number of days the refresh token is valid (default 30)
     * @return RefreshToken
     */
    public static function generate(Member $member, int $daysValid = 30): RefreshToken
    {
        $token = self::create();
        $token->Token = Uuid::uuid4()->toString();
        $token->MemberID = $member->ID;
        $token->ExpiresAt = date('Y-m-d H:i:s', strtotime("+{$daysValid} days"));
        $token->write();

        return $token;
    }

    /**
     * Find a valid refresh token
     *
     * @param string $tokenValue
     * @return RefreshToken|null
     */
    public static function findValid(string $tokenValue): ?RefreshToken
    {
        $token = self::get()->filter([
            'Token' => $tokenValue,
            'IsRevoked' => false,
        ])->first();

        if ($token && $token->isValid()) {
            return $token;
        }

        return null;
    }

    /**
     * Check if token is still valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->IsRevoked) {
            return false;
        }

        $expiresAt = strtotime($this->ExpiresAt);
        return $expiresAt > time();
    }

    /**
     * Revoke this token
     */
    public function revoke(): void
    {
        $this->IsRevoked = true;
        $this->write();
    }

    /**
     * Revoke all refresh tokens for a member
     *
     * @param Member $member
     */
    public static function revokeAllForMember(Member $member): void
    {
        $tokens = self::get()->filter([
            'MemberID' => $member->ID,
            'IsRevoked' => false,
        ]);

        foreach ($tokens as $token) {
            $token->revoke();
        }
    }
}

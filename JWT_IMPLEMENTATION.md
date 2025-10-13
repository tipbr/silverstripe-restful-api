# JWT Authentication Implementation

## Architecture

This module implements a traditional JWT authentication flow with access tokens and refresh tokens.

### Components

#### 1. JWTUtils (`src/JWT/JWTUtils.php`)

Core utility class for JWT operations. Implements singleton pattern for configuration consistency.

**Key Methods:**
- `byBasicAuth($request, $includeMemberData)` - Generate token from BasicAuth credentials
- `forMember($member, $includeMemberData)` - Generate token for a specific member (used for refresh)
- `renew($token)` - Renew a token if it's within the renewal threshold
- `check($token)` - Validate a token
- `getClaims()` - Generate default JWT claims (iss, exp, iat, rat, jti)

**Configuration:**
- `secret` - Secret key for signing tokens (should be set via environment variable)
- `lifetime_in_days` - Token lifetime (default: 7 days)
- `renew_threshold_in_minutes` - Minimum time before renewal (default: 60 minutes)

#### 2. RefreshToken Model (`src/Models/RefreshToken.php`)

DataObject for storing refresh tokens in the database.

**Fields:**
- `Token` - UUID v4 token value (indexed)
- `ExpiresAt` - Expiration timestamp
- `IsRevoked` - Boolean flag for revoked tokens
- `MemberID` - Foreign key to Member

**Key Methods:**
- `generate($member, $daysValid)` - Generate new refresh token (default: 30 days)
- `findValid($tokenValue)` - Find and validate a refresh token
- `isValid()` - Check if token is valid (not revoked and not expired)
- `revoke()` - Revoke this token
- `revokeAllForMember($member)` - Revoke all tokens for a member

#### 3. AuthController (`src/Controllers/AuthController.php`)

Handles authentication endpoints.

**Endpoints:**

##### POST `/auth/token`
- Authenticates user via BasicAuth (username/password)
- Returns access token, refresh token, and member data
- Generates new refresh token for each login

##### GET `/auth/verify`
- Validates and renews access token if needed
- Returns renewed access token

##### POST `/auth/refresh`
- Accepts `refreshToken` in request body
- Validates refresh token
- Returns new access token with same refresh token
- Does not require BasicAuth credentials

## Security Considerations

### Access Tokens
- Short-lived (7 days by default)
- Stored client-side (localStorage, sessionStorage, or memory)
- Used for API authentication via Bearer header
- Auto-renewed if within renewal threshold

### Refresh Tokens
- Long-lived (30 days by default)
- Stored in database with member association
- Can be revoked for security
- Indexed for fast lookup
- Should be stored securely client-side (httpOnly cookies recommended in production)

### Best Practices

1. **Secret Management**: Use environment variables for JWT secret
   ```yml
   FullscreenInteractive\Restful\JWT\JWTUtils:
     secret: '`JWT_SECRET`'
   ```

2. **Token Storage**: 
   - Access tokens: Memory or sessionStorage
   - Refresh tokens: httpOnly cookies (requires HTTPS)

3. **Token Rotation**: Generate new refresh token on each login for better security

4. **Revocation**: Implement logout by revoking refresh tokens
   ```php
   RefreshToken::revokeAllForMember($member);
   ```

## Flow Diagram

```
1. Login (POST /auth/token with BasicAuth)
   ├─> Validate credentials
   ├─> Generate access token (JWT)
   ├─> Generate refresh token (UUID)
   ├─> Store refresh token in database
   └─> Return both tokens + member data

2. API Request (with access token)
   ├─> Validate access token
   ├─> Check expiration
   ├─> Renew if needed (within threshold)
   └─> Execute API call

3. Refresh (POST /auth/refresh with refresh token)
   ├─> Validate refresh token (database lookup)
   ├─> Check not expired/revoked
   ├─> Generate new access token
   └─> Return new access token (keep same refresh token)

4. Logout (optional)
   └─> Revoke refresh token(s) in database
```

## Comparison with Level51 Implementation

### Similarities
- JWT encoding/decoding with same structure
- BasicAuth integration
- Token renewal mechanism
- Configuration approach

### Differences
- **Added**: Refresh token support
- **Added**: RefreshToken database model
- **Added**: `/auth/refresh` endpoint
- **Added**: `forMember()` method for token generation without BasicAuth
- **Namespace**: Changed from `Level51\JWTUtils` to `FullscreenInteractive\Restful\JWT`
- **Dependencies**: Direct dependencies instead of transitive

## Testing

After installation:

1. Run database migration:
   ```bash
   vendor/bin/sake dev/build flush=1
   ```

2. Set JWT secret:
   ```bash
   export JWT_SECRET="your-secure-random-string"
   ```

3. Test authentication:
   ```bash
   curl -X POST http://localhost/api/v1/auth/token \
     -H "Authorization: Basic base64(email:password)"
   ```

4. Test refresh:
   ```bash
   curl -X POST http://localhost/api/v1/auth/refresh \
     -H "Content-Type: application/json" \
     -d '{"refreshToken": "..."}'
   ```

# Migration Guide - JWT Utilities Internalization

## Overview

This version internalizes the JWT utilities previously provided by `level51/silverstripe-jwt-utils`. The functionality remains the same, with the addition of a traditional JWT refresh token flow.

## Breaking Changes

None - the API remains compatible with the previous implementation.

## New Features

### Refresh Token Support

The token endpoint now returns both an access token and a refresh token:

```json
{
    "token": "eyJ0eXAiOiJKV1QiL...",
    "refreshToken": "a1b2c3d4-...",
    "member": { ... }
}
```

### New Refresh Endpoint

A new `/auth/refresh` endpoint allows refreshing access tokens without re-authenticating:

```js
fetch('/api/v1/auth/refresh', {
    method: "POST",
    headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        refreshToken: refreshToken
    })
})
```

## Configuration Changes

Update your configuration from:

```yml
Level51\JWTUtils\JWTUtils:
  secret: '`JWT_SECRET`'
  lifetime_in_days: 365
  renew_threshold_in_minutes: 60
```

To:

```yml
FullscreenInteractive\Restful\JWT\JWTUtils:
  secret: '`JWT_SECRET`'
  lifetime_in_days: 7
  renew_threshold_in_minutes: 60
```

## Database Changes

A new `RefreshToken` table will be created to store refresh tokens. Run `dev/build` after updating:

```bash
vendor/bin/sake dev/build flush=1
```

## Removed Dependencies

- `level51/silverstripe-jwt-utils` - No longer required

## Added Dependencies

- `firebase/php-jwt` - JWT encoding/decoding (was already a transitive dependency)
- `nesbot/carbon` - Date/time handling (was already a transitive dependency)
- `ramsey/uuid` - UUID generation (was already a transitive dependency)

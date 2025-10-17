# Suggested GitHub Issues

This file contains ready-to-use GitHub issue templates based on the code review. Copy these into your issue tracker as needed.

---

## 🔒 Security Issues

### Issue #1: Fix Overly Permissive CORS Configuration
**Priority:** Critical  
**Labels:** security, bug  
**Effort:** Low (2-3 hours)

**Description:**
The current CORS configuration allows any origin (`Access-Control-Allow-Origin: *`) which poses a security risk for authenticated APIs.

**Current Behavior:**
```php
->addHeader('Access-Control-Allow-Origin', '*')
```

**Expected Behavior:**
- CORS should be configurable via YML config
- Should validate origin against whitelist
- Should default to restrictive settings

**Tasks:**
- [ ] Add `allowed_origins` config to ApiController
- [ ] Implement origin validation logic
- [ ] Update documentation with CORS configuration examples
- [ ] Add tests for CORS behavior

**References:**
- CODE_REVIEW.md, Section 1.1

---

### Issue #2: Implement Rate Limiting for Authentication Endpoints
**Priority:** Critical  
**Labels:** security, enhancement  
**Effort:** Medium (6-8 hours)

**Description:**
No rate limiting exists on authentication endpoints, making the API vulnerable to brute force attacks.

**Endpoints Affected:**
- `/token` (login)
- `/refresh` (token refresh)

**Proposed Solution:**
- Implement rate limiting using SilverStripe cache
- Configure limits per IP address
- Add configurable thresholds (requests per minute)

**Tasks:**
- [ ] Create rate limiting trait or middleware
- [ ] Add configuration options
- [ ] Implement per-IP tracking
- [ ] Add appropriate HTTP 429 responses
- [ ] Document rate limiting behavior
- [ ] Add tests

**References:**
- CODE_REVIEW.md, Section 1.1

---

### Issue #3: Add Input Validation and Sanitization
**Priority:** High  
**Labels:** security, enhancement  
**Effort:** Low (3-4 hours)

**Description:**
Variables retrieved via `getVar()` are not sanitized, potentially allowing injection attacks.

**Current Code:**
```php
public function getVar(string $name): mixed
{
    $key = strtolower($name);
    return (isset($this->vars[$key])) ? $this->vars[$key] : null;
}
```

**Tasks:**
- [ ] Add optional sanitization to `getVar()`
- [ ] Create validation trait
- [ ] Add input type checking
- [ ] Update documentation
- [ ] Add tests

**References:**
- CODE_REVIEW.md, Section 1.2

---

### Issue #4: Implement Refresh Token Rotation
**Priority:** High  
**Labels:** security, enhancement  
**Effort:** Medium (4-6 hours)

**Description:**
Refresh tokens should be rotated (invalidated and reissued) when used to prevent token reuse attacks.

**Current Behavior:**
- Refresh token remains valid after use
- No limit on token reuse

**Expected Behavior:**
- Old token revoked when refresh endpoint used
- New refresh token issued
- Configurable token limits per user

**Tasks:**
- [ ] Update `AuthController::refresh()` to revoke old token
- [ ] Issue new refresh token on each refresh
- [ ] Add max tokens per user limit
- [ ] Add cleanup task for expired tokens
- [ ] Update tests

**References:**
- CODE_REVIEW.md, Section 1.1

---

### Issue #5: Add JWT Secret Strength Validation
**Priority:** High  
**Labels:** security, enhancement  
**Effort:** Low (1-2 hours)

**Description:**
JWT secret should be validated for minimum length/complexity on initialization.

**Tasks:**
- [ ] Add validation in `JWTUtils::__construct()`
- [ ] Require minimum 32 character secret
- [ ] Throw exception if secret is weak
- [ ] Update documentation with secret requirements

**References:**
- CODE_REVIEW.md, Section 1.1

---

## 🏗️ Architecture Improvements

### Issue #6: Convert UUIDable Extension to Trait
**Priority:** Medium  
**Labels:** enhancement, refactoring  
**Effort:** Low (2-3 hours)

**Description:**
The UUIDable functionality would be more flexible as a trait while maintaining the extension for backward compatibility.

**Benefits:**
- Better IDE support
- Reusable in non-DataObject classes
- More explicit about functionality

**Tasks:**
- [ ] Create `src/Traits/Uuidable.php`
- [ ] Add `getByUUID()` helper method
- [ ] Keep extension for backward compatibility
- [ ] Update documentation to recommend trait
- [ ] Add deprecation notice to extension (for v3.0)

**References:**
- CODE_REVIEW.md, Section 2.1

---

### Issue #7: Extract Authentication Logic to Trait
**Priority:** Medium  
**Labels:** enhancement, refactoring  
**Effort:** Medium (4-6 hours)

**Description:**
JWT authentication logic in ApiController should be extracted to a reusable trait.

**Methods to Extract:**
- `ensureUserLoggedIn()`
- `getJwt()`
- `getBearerToken()`
- `getAuthorizationHeader()`

**Tasks:**
- [ ] Create `src/Traits/JwtAuthentication.php`
- [ ] Move authentication methods to trait
- [ ] Update ApiController to use trait
- [ ] Ensure backward compatibility
- [ ] Add tests

**References:**
- CODE_REVIEW.md, Section 2.2

---

### Issue #8: Extract Response Formatting to Trait
**Priority:** Medium  
**Labels:** enhancement, refactoring  
**Effort:** Medium (4-6 hours)

**Description:**
Response formatting methods should be in a separate trait for better code organization.

**Methods to Extract:**
- `success()`
- `failure()`
- `returnArray()`
- `returnJSON()`
- `returnPaginated()`
- `prepList()`
- `prepPaginatedOutput()`

**Tasks:**
- [ ] Create `src/Traits/JsonResponse.php`
- [ ] Move response methods to trait
- [ ] Update ApiController to use trait
- [ ] Ensure backward compatibility
- [ ] Add tests

**References:**
- CODE_REVIEW.md, Section 2.2

---

### Issue #9: Implement Middleware for CORS and JSON Processing
**Priority:** Medium  
**Labels:** enhancement, refactoring  
**Effort:** Medium (4-6 hours)

**Description:**
CORS and JSON request processing should be handled by middleware instead of in controller init().

**Tasks:**
- [ ] Create `src/Middleware/CorsMiddleware.php`
- [ ] Create `src/Middleware/JsonMiddleware.php`
- [ ] Register middleware in config
- [ ] Update ApiController to remove duplicate logic
- [ ] Add tests

**References:**
- CODE_REVIEW.md, Section 2.3

---

### Issue #10: Standardize Error Response Format
**Priority:** Medium  
**Labels:** enhancement  
**Effort:** Low (2-3 hours)

**Description:**
Error responses should follow a consistent format across all endpoints.

**Proposed Format:**
```json
{
  "error": {
    "message": "Error description",
    "code": 400,
    "type": "ValidationError",
    "timestamp": 1234567890,
    "details": {}
  }
}
```

**Tasks:**
- [ ] Create standardized error response method
- [ ] Update all error responses to use new format
- [ ] Document error response structure
- [ ] Add tests

**References:**
- CODE_REVIEW.md, Section 4.1

---

## 📚 Documentation

### Issue #11: Create Comprehensive README
**Priority:** High  
**Labels:** documentation  
**Effort:** Medium (4-6 hours)

**Description:**
Module needs a comprehensive README with installation, configuration, and usage examples.

**Sections Needed:**
- [ ] Installation instructions
- [ ] Basic configuration
- [ ] JWT setup and security
- [ ] CORS configuration
- [ ] Usage examples
- [ ] API endpoint documentation
- [ ] Common use cases
- [ ] Troubleshooting

**References:**
- CODE_REVIEW.md, Section 7.1

---

### Issue #12: Create SECURITY.md Document
**Priority:** High  
**Labels:** documentation, security  
**Effort:** Low (2-3 hours)

**Description:**
Security best practices and vulnerability reporting process should be documented.

**Sections Needed:**
- [ ] Security best practices
- [ ] JWT secret management
- [ ] CORS configuration guidelines
- [ ] Rate limiting recommendations
- [ ] Vulnerability reporting process
- [ ] Security update policy

**References:**
- CODE_REVIEW.md, Section 7.1

---

### Issue #13: Create API Documentation
**Priority:** Medium  
**Labels:** documentation  
**Effort:** Medium (4-6 hours)

**Description:**
Complete API reference documentation for all endpoints.

**Endpoints to Document:**
- [ ] `/token` - Initial authentication
- [ ] `/verify` - Token verification
- [ ] `/refresh` - Token refresh
- [ ] Custom endpoint patterns
- [ ] Error responses
- [ ] Request/response examples

**References:**
- CODE_REVIEW.md, Section 7.1

---

### Issue #14: Add PHPDoc Blocks to All Public Methods
**Priority:** Low  
**Labels:** documentation, code-quality  
**Effort:** Medium (4-6 hours)

**Description:**
All public methods should have complete PHPDoc blocks for better IDE support and documentation generation.

**Tasks:**
- [ ] Add PHPDoc to ApiController methods
- [ ] Add PHPDoc to AuthController methods
- [ ] Add PHPDoc to JWTUtils methods
- [ ] Add PHPDoc to RefreshToken methods
- [ ] Add PHPDoc to interfaces and traits

**References:**
- CODE_REVIEW.md, Section 7.2

---

## 🧪 Testing

### Issue #15: Create Unit Test Suite
**Priority:** High  
**Labels:** testing  
**Effort:** High (8-10 hours)

**Description:**
Module needs comprehensive unit test coverage.

**Test Files Needed:**
- [ ] `tests/JWT/JWTUtilsTest.php`
- [ ] `tests/Models/RefreshTokenTest.php`
- [ ] `tests/Controllers/ApiControllerTest.php`
- [ ] `tests/Controllers/AuthControllerTest.php`

**Key Tests:**
- [ ] JWT token generation and validation
- [ ] Token renewal logic
- [ ] Refresh token lifecycle
- [ ] Input validation
- [ ] Error handling

**References:**
- CODE_REVIEW.md, Section 6.1

---

### Issue #16: Create Integration Tests
**Priority:** Medium  
**Labels:** testing  
**Effort:** High (6-8 hours)

**Description:**
Integration tests for complete authentication workflows.

**Test Scenarios:**
- [ ] Complete login flow with basic auth
- [ ] Token refresh flow
- [ ] Token expiration handling
- [ ] Invalid credentials handling
- [ ] Protected endpoint access
- [ ] CORS preflight requests

**References:**
- CODE_REVIEW.md, Section 6.2

---

## 🚀 Features & Enhancements

### Issue #17: Add Logout Endpoint
**Priority:** Medium  
**Labels:** enhancement  
**Effort:** Low (2-3 hours)

**Description:**
Add logout endpoint to revoke refresh tokens.

**Features:**
- [ ] Revoke single refresh token
- [ ] Option to revoke all tokens (logout all devices)
- [ ] Return success response

**Endpoint:**
```
POST /api/auth/logout
Body: { "refreshToken": "...", "logoutAll": false }
```

**References:**
- CODE_REVIEW.md, Section 9.2

---

### Issue #18: Add Token Cleanup Task
**Priority:** Medium  
**Labels:** enhancement  
**Effort:** Low (2-3 hours)

**Description:**
Create scheduled task to cleanup expired refresh tokens.

**Tasks:**
- [ ] Create `CleanupExpiredTokensTask` class
- [ ] Delete tokens where `ExpiresAt < NOW()`
- [ ] Add logging for cleanup operations
- [ ] Document cron setup

**References:**
- CODE_REVIEW.md, Section 9.3

---

### Issue #19: Implement Request Validation Trait
**Priority:** Medium  
**Labels:** enhancement  
**Effort:** Medium (4-6 hours)

**Description:**
Create reusable validation trait for request input validation.

**Features:**
- [ ] Type validation
- [ ] Required field checking
- [ ] Custom validation callbacks
- [ ] Standardized validation error responses

**Usage Example:**
```php
$data = $this->validate([
    'email' => ['required' => true, 'type' => 'email'],
    'password' => ['required' => true, 'type' => 'string'],
]);
```

**References:**
- CODE_REVIEW.md, Section 4.2

---

### Issue #20: Add API Versioning Support
**Priority:** Low  
**Labels:** enhancement  
**Effort:** Medium (4-6 hours)

**Description:**
Support for API versioning to allow backward-compatible changes.

**Proposed Structure:**
```php
class ApiV1Controller extends ApiController {
    private static $url_segment = 'api/v1';
}

class ApiV2Controller extends ApiController {
    private static $url_segment = 'api/v2';
}
```

**Tasks:**
- [ ] Create base versioned controller structure
- [ ] Document versioning strategy
- [ ] Update routing configuration
- [ ] Add migration guide

**References:**
- CODE_REVIEW.md, Section 12.2

---

## 📋 Configuration & Setup

### Issue #21: Create .env.example File
**Priority:** High  
**Labels:** documentation  
**Effort:** Low (1 hour)

**Description:**
Provide example environment configuration file.

**Variables to Include:**
- [ ] JWT_SECRET
- [ ] API_ALLOWED_ORIGIN_*
- [ ] JWT_ISSUER
- [ ] Rate limiting settings

**References:**
- CODE_REVIEW.md, Section 10.2

---

### Issue #22: Enhance Configuration Options
**Priority:** Medium  
**Labels:** enhancement  
**Effort:** Low (2-3 hours)

**Description:**
Add comprehensive configuration options with sensible defaults.

**Configuration Needed:**
- [ ] CORS allowed origins
- [ ] Rate limiting thresholds
- [ ] Default pagination settings
- [ ] JWT algorithm options
- [ ] Refresh token settings

**References:**
- CODE_REVIEW.md, Section 10.1

---

## 🔄 Backward Compatibility

### Issue #23: Add Deprecation Notices for v3.0
**Priority:** Low  
**Labels:** maintenance  
**Effort:** Low (1-2 hours)

**Description:**
Add deprecation notices to code that will be removed in v3.0.

**Items to Deprecate:**
- [ ] UUIDable Extension (in favor of trait)
- [ ] Old error response format
- [ ] Any breaking changes planned for v3.0

**Tasks:**
- [ ] Add deprecation notices
- [ ] Update UPGRADE.md
- [ ] Document migration path

**References:**
- CODE_REVIEW.md, Section 11.1

---

## 📊 Implementation Roadmap

Based on priorities and dependencies:

### Sprint 1: Security & Critical Items (Week 1)
- Issue #1: CORS Configuration
- Issue #2: Rate Limiting  
- Issue #3: Input Validation
- Issue #5: JWT Secret Validation
- Issue #21: .env.example

### Sprint 2: Architecture (Week 2)
- Issue #4: Token Rotation
- Issue #6: UUIDable Trait
- Issue #7: Authentication Trait
- Issue #8: Response Trait
- Issue #10: Error Standardization

### Sprint 3: Documentation (Week 3)
- Issue #11: README
- Issue #12: SECURITY.md
- Issue #13: API Documentation
- Issue #14: PHPDoc Blocks

### Sprint 4: Testing & Features (Week 4)
- Issue #15: Unit Tests
- Issue #16: Integration Tests
- Issue #17: Logout Endpoint
- Issue #18: Cleanup Task

### Sprint 5: Enhancements (Week 5+)
- Issue #9: Middleware
- Issue #19: Validation Trait
- Issue #20: API Versioning
- Issue #22: Configuration Enhancement
- Issue #23: Deprecation Notices

---

*Copy individual issues into GitHub as needed. Adjust priorities based on your specific requirements.*

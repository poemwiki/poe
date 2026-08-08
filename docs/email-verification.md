# Email Verification for Login

## Overview

This document describes the email verification requirement for password-based authentication in the poemwiki API.

## Behavior

### Password-based Login (`POST /api/v1/user/login`)

For users who have a password set (non-social accounts):
- If `email_verified_at` is `null`, login will return a `403` error with `error: 'email_not_verified'`
- If `email_verified_at` is set, login proceeds normally

### Social Login

Social login flows (WeChat, WeApp) are unaffected and continue to work regardless of email verification status.

## API Endpoints

### Resend Verification Email

**Endpoint:** `POST /api/v1/user/email/resend`  
**Authentication:** Required (`auth:api`)

**Response for verified users:**
```json
{
  "message": "already_verified"
}
```

**Response for unverified users:**
```json
{
  "message": "verification_link_sent"
}
```

### Error Response for Unverified Login

**Status Code:** `403`
```json
{
  "message": "Email not verified",
  "data": {
    "error": "email_not_verified"
  }
}
```

## Implementation Details

- Email verification check only applies to users with non-empty passwords
- Social accounts (empty password) bypass the verification requirement
- EventServiceProvider already configured to send verification emails on registration
- Uses Laravel's built-in `MustVerifyEmail` interface and `hasVerifiedEmail()` method
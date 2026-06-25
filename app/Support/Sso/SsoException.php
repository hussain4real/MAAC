<?php

namespace App\Support\Sso;

use RuntimeException;

/**
 * A controlled failure in the SSO login flow (token exchange, userinfo retrieval,
 * missing identity claims, or a provisioning policy rejection). The controller
 * surfaces it as a friendly error on the login screen rather than a 500.
 */
class SsoException extends RuntimeException
{
    //
}

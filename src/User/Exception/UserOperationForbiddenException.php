<?php

namespace App\User\Exception;

/**
 * Operation refused for business reasons (e.g. an administrator deleting or
 * demoting their own account).
 */
class UserOperationForbiddenException extends \RuntimeException
{
}

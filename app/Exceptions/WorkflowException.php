<?php

namespace App\Exceptions;

use RuntimeException;

/** Raised when a submission/review action is not allowed in the article's current state. */
class WorkflowException extends RuntimeException
{
}

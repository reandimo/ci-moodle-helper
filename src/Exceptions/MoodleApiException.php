<?php

namespace CiMoodleHelper\MoodleApi\Exceptions;

/**
 * Base exception class for Moodle API errors
 */
class MoodleApiException extends \Exception
{
    protected $context = [];

    public function __construct($message = "", $code = 0, \Throwable $previous = null, $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }
}

/**
 * Exception thrown when Moodle API configuration is invalid
 */
class ConfigurationException extends MoodleApiException
{
    public function __construct($message = "Invalid Moodle API configuration", $context = [])
    {
        parent::__construct($message, 1001, null, $context);
    }
}

/**
 * Exception thrown when Moodle API request fails
 */
class RequestException extends MoodleApiException
{
    public function __construct($message = "Moodle API request failed", $context = [])
    {
        parent::__construct($message, 1002, null, $context);
    }
}

/**
 * Exception thrown when Moodle API returns an error response
 */
class ApiException extends MoodleApiException
{
    public function __construct($message = "Moodle API error", $context = [])
    {
        parent::__construct($message, 1003, null, $context);
    }
}

/**
 * Exception thrown when user operation fails
 */
class UserException extends MoodleApiException
{
    public function __construct($message = "User operation failed", $context = [])
    {
        parent::__construct($message, 1004, null, $context);
    }
}

/**
 * Exception thrown when course operation fails
 */
class CourseException extends MoodleApiException
{
    public function __construct($message = "Course operation failed", $context = [])
    {
        parent::__construct($message, 1005, null, $context);
    }
}

/**
 * Exception thrown when enrollment operation fails
 */
class EnrollmentException extends MoodleApiException
{
    public function __construct($message = "Enrollment operation failed", $context = [])
    {
        parent::__construct($message, 1006, null, $context);
    }
}

/**
 * Exception thrown when group operation fails
 */
class GroupException extends MoodleApiException
{
    public function __construct($message = "Group operation failed", $context = [])
    {
        parent::__construct($message, 1007, null, $context);
    }
}

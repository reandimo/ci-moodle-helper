# CI Moodle Helper - Moodle API Library

A comprehensive PHP library for interacting with Moodle's REST API. This library provides easy-to-use methods for user management, course operations, enrollment, group management, and more.

## Features

- **User Management**: Create, update, and retrieve user information
- **Course Operations**: Get courses, course groups, and course-related data
- **Enrollment Management**: Enroll and unenroll users from courses
- **Group Management**: Create groups, add members, and manage group operations
- **Activity Completion**: Mark activities as completed and track progress
- **Grade Management**: Retrieve user grades and course grade items
- **Authentication**: Generate user authentication URLs
- **Error Handling**: Comprehensive error handling with try-catch blocks

## Installation

### Via Composer

```bash
composer require ci-moodle-helper/moodle-api
```

### Manual Installation

1. Clone or download this repository
2. Include the autoloader in your project:

```php
require_once 'vendor/autoload.php';
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use CiMoodleHelper\MoodleApi\MoodleApi;

// Initialize the Moodle API client
$moodleApi = new MoodleApi(
    'your_moodle_token_here',
    'https://your-moodle-site.com/webservice/rest/server.php'
);

// Get all courses
$courses = $moodleApi->getCourses();
if ($courses) {
    foreach ($courses as $course) {
        echo $course['fullname'] . "\n";
    }
}

// Create a new user
$newUser = [
    'username' => 'newuser123',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'email' => 'john.doe@example.com',
    'createpassword' => 1
];

$createdUser = $moodleApi->createUser($newUser);
```

## Configuration

### Setting Up Moodle Web Services

1. In your Moodle site, go to **Site administration > Advanced features**
2. Enable **Web services**
3. Go to **Site administration > Server > Web services > External services**
4. Create a new external service or use the default one
5. Add the required functions to your service
6. Go to **Site administration > Server > Web services > Manage tokens**
7. Create a token for a user with appropriate permissions

### Required Moodle Functions

Make sure your Moodle web service has access to these functions:

- `core_course_get_courses`
- `core_group_get_course_groups`
- `core_user_get_users_by_field`
- `core_course_get_courses_by_field`
- `core_user_create_users`
- `core_completion_override_activity_completion_status`
- `enrol_manual_enrol_users`
- `core_enrol_unenrol_user_enrolment`
- `core_group_add_group_members`
- `core_group_get_group_members`
- `core_user_update_users`
- `core_completion_get_activities_completion_status`
- `gradereport_user_get_grade_items`
- `core_enrol_get_enrolled_users`
- `auth_userkey_request_login_url`
- `core_group_create_groups`

## API Reference

### Constructor

```php
new MoodleApi($token, $server)
```

- `$token` (string): Your Moodle API token
- `$server` (string): Your Moodle server URL

### User Management

#### `getUserByField($field, $value)`
Get user information by field (username, email, etc.)

#### `createUser($userData)`
Create a new user in Moodle

#### `updateUser($userId, $data)`
Update user information

### Course Management

#### `getCourses()`
Get all courses from Moodle

#### `getCourseByField($field, $value)`
Get course information by field

#### `getCourseGroups($courseId)`
Get groups for a specific course

### Enrollment Management

#### `enrollUser($enrollmentData)`
Enroll a user in a course

#### `unenrollUser($enrollmentUserId)`
Unenroll a user from a course

#### `getUsersEnrolled($courseId)`
Get all enrolled users in a course

### Group Management

#### `createGroup($courseId, $groupName)`
Create a new group in a course

#### `addGroupMember($groupId, $userId)`
Add a user to a group

#### `getGroupMembers($groupId)`
Get all members of a group

#### `getGroup($courseId, $groupName)`
Get group information by name

### Activity Management

#### `completeActivity($userId, $moduleId)`
Mark an activity as completed for a user

#### `getCompletedActivities($userId, $courseId)`
Get all completed activities for a user in a course

#### `overrideActivityCompletionStatus($cmId, $userId)`
Override activity completion status

### Grade Management

#### `getGradeItemsCourse($userId, $courseId)`
Get grade items for a user in a course

### Authentication

#### `generateUserAuthUrl($fieldMapping)`
Generate a user authentication URL

### Utility Methods

#### `formatUsername($rut)`
Format RUT for username

#### `formatUsernameSence($rut)`
Format RUT for SENCE username

## Error Handling

The library includes comprehensive error handling with custom exception classes. All methods throw specific exceptions instead of returning `false`, making error handling more robust and informative.

### Exception Types

- **`ConfigurationException`**: Thrown when API configuration is invalid
- **`RequestException`**: Thrown when HTTP requests fail
- **`ApiException`**: Thrown when Moodle API returns an error
- **`UserException`**: Thrown when user operations fail
- **`CourseException`**: Thrown when course operations fail
- **`EnrollmentException`**: Thrown when enrollment operations fail
- **`GroupException`**: Thrown when group operations fail

### Basic Error Handling

```php
<?php

use CiMoodleHelper\MoodleApi\MoodleApi;
use CiMoodleHelper\MoodleApi\Exceptions\{
    ConfigurationException,
    UserException,
    CourseException
};

try {
    // Initialize with empty values (will throw ConfigurationException)
    $moodleApi = new MoodleApi();
} catch (ConfigurationException $e) {
    echo "Configuration error: " . $e->getMessage();
    echo "Context: " . json_encode($e->getContext());
}

try {
    // Initialize with valid credentials
    $moodleApi = new MoodleApi(
        'your_moodle_token_here',
        'https://your-moodle-site.com/webservice/rest/server.php'
    );
    
    // Get courses
    $courses = $moodleApi->getCourses();
    echo "Found " . count($courses) . " courses\n";
    
} catch (CourseException $e) {
    echo "Course operation failed: " . $e->getMessage();
    echo "Context: " . json_encode($e->getContext());
} catch (ConfigurationException $e) {
    echo "Configuration error: " . $e->getMessage();
}
```

### Advanced Error Handling

```php
<?php

use CiMoodleHelper\MoodleApi\MoodleApi;
use CiMoodleHelper\MoodleApi\Exceptions\MoodleApiException;

try {
    $moodleApi = new MoodleApi($token, $server);
    
    // Create user with validation
    $newUser = [
        'username' => 'testuser123',
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com'
    ];
    
    $createdUser = $moodleApi->createUser($newUser);
    echo "User created with ID: " . $createdUser[0]['id'] . "\n";
    
} catch (UserException $e) {
    // Handle user-specific errors
    $context = $e->getContext();
    
    if (isset($context['missing_field'])) {
        echo "Missing required field: " . $context['missing_field'];
    } else {
        echo "User creation failed: " . $e->getMessage();
    }
    
} catch (MoodleApiException $e) {
    // Handle any Moodle API error
    echo "Moodle API error: " . $e->getMessage();
    echo "Error code: " . $e->getCode();
    echo "Context: " . json_encode($e->getContext());
    
} catch (Exception $e) {
    // Handle any other error
    echo "Unexpected error: " . $e->getMessage();
}
```

### Configuration Validation

The library validates configuration on initialization:

```php
<?php

try {
    // This will throw ConfigurationException
    $moodleApi = new MoodleApi('', ''); // Empty token and server
} catch (ConfigurationException $e) {
    $context = $e->getContext();
    echo "Token provided: " . ($context['token_provided'] ? 'Yes' : 'No');
    echo "Server provided: " . ($context['server_provided'] ? 'Yes' : 'No');
}

try {
    // This will also throw ConfigurationException
    $moodleApi = new MoodleApi('valid_token', ''); // Empty server
} catch (ConfigurationException $e) {
    echo "Server URL is required";
}
```

## Examples

See the `examples/` directory for more detailed usage examples:

- `basic_usage.php` - Basic usage examples
- More examples coming soon...

## Dependencies

This library depends on the following Composer packages:

- **llagerlof/moodlerest**: A PHP library for Moodle REST API communication
  - Automatically installed when you install this package
  - Provides the underlying HTTP client for Moodle API calls
  - Handles authentication, request formatting, and response parsing

## Requirements

- PHP 7.4 or higher
- cURL extension
- Valid Moodle installation with web services enabled
- **llagerlof/moodlerest** Composer package (automatically installed)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions, please open an issue on the GitHub repository.

## Changelog

### Version 1.0.0
- Initial release
- Complete Moodle API integration
- User management
- Course operations
- Enrollment management
- Group management
- Activity completion tracking
- Grade management
- Authentication URL generation

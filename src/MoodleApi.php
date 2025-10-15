<?php

namespace CiMoodleHelper\MoodleApi;

use Exception;
use llagerlof\MoodleRest;
use CiMoodleHelper\MoodleApi\Exceptions\{
    MoodleApiException,
    ConfigurationException,
    RequestException,
    ApiException,
    UserException,
    CourseException,
    EnrollmentException,
    GroupException
};

/**
 * Moodle API Client
 * 
 * A comprehensive PHP library for interacting with Moodle's REST API
 * Provides methods for user management, course operations, enrollment, and more.
 */
class MoodleApi
{
    protected $moodleToken;
    protected $moodleServer;
    protected $moodleRest;

    /**
     * Constructor
     *
     * @param string $token Moodle API token
     * @param string $server Moodle server URL
     * @throws ConfigurationException
     */
    public function __construct($token = null, $server = null)
    {
        $this->moodleToken = $token ?: '';
        $this->moodleServer = $server ?: '';
        
        if (empty($this->moodleToken) || empty($this->moodleServer)) {
            throw new ConfigurationException(
                'Moodle token and server URL are required',
                [
                    'token_provided' => !empty($this->moodleToken),
                    'server_provided' => !empty($this->moodleServer)
                ]
            );
        }
        
        try {
            $this->moodleRest = new MoodleRest($this->moodleServer, $this->moodleToken);
        } catch (Exception $e) {
            throw new ConfigurationException(
                'Failed to initialize Moodle REST client: ' . $e->getMessage(),
                ['original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Set the Moodle server URL
     *
     * @param string $server
     * @throws ConfigurationException
     * @return void
     */
    public function setServer($server)
    {
        if (empty($server)) {
            throw new ConfigurationException('Server URL cannot be empty');
        }
        
        $this->moodleServer = $server;
        
        try {
            $this->moodleRest = new MoodleRest($this->moodleServer, $this->moodleToken);
        } catch (Exception $e) {
            throw new ConfigurationException(
                'Failed to update Moodle REST client with new server: ' . $e->getMessage(),
                ['server' => $server, 'original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Set the Moodle API token
     *
     * @param string $token
     * @throws ConfigurationException
     * @return void
     */
    public function setToken($token)
    {
        if (empty($token)) {
            throw new ConfigurationException('Token cannot be empty');
        }
        
        $this->moodleToken = $token;
        
        try {
            $this->moodleRest = new MoodleRest($this->moodleServer, $this->moodleToken);
        } catch (Exception $e) {
            throw new ConfigurationException(
                'Failed to update Moodle REST client with new token: ' . $e->getMessage(),
                ['original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get all courses
     *
     * @return array
     * @throws CourseException
     */
    public function getCourses()
    {
        try {
            $json = $this->moodleRest->request('core_course_get_courses');
            $result = json_decode($json, true);
            
            if ($result === null) {
                throw new CourseException(
                    'Failed to decode JSON response from getCourses',
                    ['raw_response' => $json]
                );
            }
            
            return $result;
        } catch (Exception $e) {
            if ($e instanceof CourseException) {
                throw $e;
            }
            throw new CourseException(
                'Failed to get courses: ' . $e->getMessage(),
                ['original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get course groups
     *
     * @param int $courseId
     * @return array|false
     */
    public function getCourseGroups($courseId)
    {
        try {
            $parameters = [
                'courseid' => $courseId,
            ];

            $json = $this->moodleRest->request('core_group_get_course_groups', $parameters);
            return json_decode($json, true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user by field
     *
     * @param string $field
     * @param string $value
     * @return array|false
     */
    public function getUserByField($field, $value)
    {
        try {
            $parameters = [
                'field'  => $field,
                'values' => [$value],
            ];

            $json = $this->moodleRest->request('core_user_get_users_by_field', $parameters);
            $json = json_decode($json, true);

            if (!empty($json)) {
                return $json;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get course by field
     *
     * @param string $field
     * @param string $value
     * @return array|false
     */
    public function getCourseByField($field, $value)
    {
        try {
            $parameters = [
                'field' => $field,
                'value' => $value,
            ];

            $json = $this->moodleRest->request('core_course_get_courses_by_field', $parameters);
            $json = json_decode($json, true);

            if (isset($json['courses'])) {
                return $json;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a new Moodle user
     *
     * @param array $user User data array
     * @return array
     * @throws UserException
     */
    public function createUser($user)
    {
        try {
            // Validate required fields
            $requiredFields = ['username', 'firstname', 'lastname', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($user[$field])) {
                    throw new UserException(
                        "Required field '{$field}' is missing or empty",
                        ['user_data' => $user, 'missing_field' => $field]
                    );
                }
            }

            $parameters = [
                'users' => [
                    [
                        'createpassword' => $user['createpassword'] ?? 1,
                        'username'       => $user['username'],
                        'firstname'      => $user['firstname'],
                        'lastname'       => $user['lastname'],
                        'email'          => $user['email'],
                        'country'        => $user['country'] ?? 'CL',
                    ],
                ],
            ];

            $json = $this->moodleRest->request('core_user_create_users', $parameters);
            $result = json_decode($json, true);

            if ($result === null) {
                throw new UserException(
                    'Failed to decode JSON response from createUser',
                    ['raw_response' => $json, 'user_data' => $user]
                );
            }

            if (!isset($result[0]['id'])) {
                throw new UserException(
                    'User creation failed - no ID returned',
                    ['response' => $result, 'user_data' => $user]
                );
            }

            return $result;
        } catch (Exception $e) {
            if ($e instanceof UserException) {
                throw $e;
            }
            throw new UserException(
                'Failed to create user: ' . $e->getMessage(),
                ['user_data' => $user, 'original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Override activity completion status
     *
     * @param int $cmId Course module ID
     * @param int $userId User ID
     * @return array|false
     */
    public function overrideActivityCompletionStatus($cmId, $userId)
    {
        try {
            $parameters = [
                'newstate' => 1,
                'cmid' => $cmId,
                'userid' => $userId
            ];
            
            $json = $this->moodleRest->request('core_completion_override_activity_completion_status', $parameters);
            return json_decode($json, true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Enroll user in course
     *
     * @param array $enrollments Enrollment data
     * @return bool
     * @throws EnrollmentException
     */
    public function enrollUser($enrollments)
    {
        try {
            // Validate enrollment data
            if (empty($enrollments['enrolments'])) {
                throw new EnrollmentException(
                    'Enrollment data is missing or invalid',
                    ['enrollment_data' => $enrollments]
                );
            }

            foreach ($enrollments['enrolments'] as $enrollment) {
                if (empty($enrollment['userid']) || empty($enrollment['courseid']) || empty($enrollment['roleid'])) {
                    throw new EnrollmentException(
                        'Required enrollment fields are missing (userid, courseid, roleid)',
                        ['enrollment' => $enrollment]
                    );
                }
            }

            $response = $this->moodleRest->request('enrol_manual_enrol_users', $enrollments);
            
            // Check if enrollment was successful
            if ($response !== null) {
                throw new EnrollmentException(
                    'Enrollment failed - non-null response received',
                    ['response' => $response, 'enrollment_data' => $enrollments]
                );
            }
            
            return true;
        } catch (Exception $e) {
            if ($e instanceof EnrollmentException) {
                throw $e;
            }
            throw new EnrollmentException(
                'Failed to enroll user: ' . $e->getMessage(),
                ['enrollment_data' => $enrollments, 'original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Unenroll user from course
     *
     * @param int $enrollUserId Enrollment user ID
     * @return bool
     */
    public function unenrollUser($enrollUserId)
    {
        try {
            $parameters = [
                'ueid' => $enrollUserId,
            ];

            $response = $this->moodleRest->request('core_enrol_unenrol_user_enrolment', $parameters);
            return (bool) ($response === null);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add member to group
     *
     * @param int $groupId Group ID
     * @param int $userId User ID
     * @return bool
     */
    public function addGroupMember($groupId, $userId)
    {
        try {
            $parameters = [
                'members' => [
                    [
                        'groupid' => $groupId,
                        'userid'  => $userId,
                    ],
                ],
            ];

            $res = $this->moodleRest->request('core_group_add_group_members', $parameters);
            
            if (!(bool)($res === null || $res === 'null')) {
                $res = json_decode($res);
                if (isset($res->debuginfo) && strtolower(str_replace(' ', '_', $res->debuginfo)) == 'only_enrolled_users_may_be_members_of_groups') {
                    return true; // If user is not enrolled, don't try to add to group again
                }
            }
            
            return (bool) ($res === null || $res === 'null');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get group members
     *
     * @param int $groupId Group ID
     * @return array|false
     */
    public function getGroupMembers($groupId)
    {
        try {
            $parameters = [
                'groupids' => [
                    $groupId,
                ],
            ];

            $json = $this->moodleRest->request('core_group_get_group_members', $parameters);
            $json = json_decode($json, true);

            if (isset($json[0]['userids'])) {
                return $json[0]['userids'];
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update user information
     *
     * @param int $userId User ID
     * @param array $data User data to update
     * @return bool
     */
    public function updateUser($userId, $data)
    {
        try {
            if ($userId === null || $data === null) {
                return false;
            }

            $parameters['users'][0]['id'] = (int) $userId;

            foreach ($data as $key => $value) {
                $parameters['users'][0][$key] = (string) $value;
            }

            $json = $this->moodleRest->request('core_user_update_users', $parameters);
            $json = json_decode($json, true);

            return !(isset($json['errorcode']));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Format username from RUT
     *
     * @param string $rut
     * @return string
     */
    public function formatUsername($rut)
    {
        return str_replace(['-', '.', 'K', 'k'], '', $rut);
    }

    /**
     * Format username from RUT for SENCE
     *
     * @param string $rut
     * @return string
     */
    public function formatUsernameSence($rut)
    {
        $format = explode('-', $rut)[0];
        return str_replace(['.', 'K', 'k'], '', $format);
    }

    /**
     * Complete activity for user
     *
     * @param int $userId User ID
     * @param int $modId Module ID
     * @return array|false
     */
    public function completeActivity($userId, $modId)
    {
        try {
            if ($userId === null || $modId === null) {
                return false;
            }

            $parameters['userid']   = (int) $userId;
            $parameters['cmid']     = (int) $modId;
            $parameters['newstate'] = 1;

            $json = $this->moodleRest->request('core_completion_override_activity_completion_status', $parameters);
            $json = json_decode($json);

            if (isset($json->state) && $json->state === 1) {
                return $json;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get completed activities for user in course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array|false
     */
    public function getCompletedActivities($userId, $courseId)
    {
        try {
            if ($userId === null || $courseId === null) {
                return false;
            }

            $parameters['userid']   = (int) $userId;
            $parameters['courseid'] = (int) $courseId;

            $json = $this->moodleRest->request('core_completion_get_activities_completion_status', $parameters);
            $json = json_decode($json);

            if (isset($json->statuses)) {
                return $json->statuses;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get grade items for user in course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array|false
     */
    public function getGradeItemsCourse($userId, $courseId)
    {
        try {
            if ($userId === null || $courseId === null) {
                return false;
            }

            $parameters['userid']   = (int) $userId;
            $parameters['courseid'] = (int) $courseId;

            $json = $this->moodleRest->request('gradereport_user_get_grade_items', $parameters);
            $json = json_decode($json);
            
            if (isset($json->usergrades)) {
                return $json->usergrades[0];
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get enrolled users in course
     *
     * @param int $courseId Course ID
     * @return array|false
     */
    public function getUsersEnrolled($courseId)
    {
        try {
            $parameters = [
                'courseid' => $courseId,
            ];

            $json = $this->moodleRest->request('core_enrol_get_enrolled_users', $parameters);
            return json_decode($json, true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generate user authentication URL
     *
     * @param array $fieldMapping Field mapping for user authentication
     * @return string|false
     */
    public function generateUserAuthUrl($fieldMapping)
    {
        try {
            $parameters = [
                'user' => $fieldMapping,
            ];

            $json = $this->moodleRest->request('auth_userkey_request_login_url', $parameters);
            $json = json_decode($json, true);

            if (isset($json['loginurl'])) {
                return $json['loginurl'];
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get group by course and name
     *
     * @param int $courseId Course ID
     * @param string $groupName Group name
     * @return array|false
     */
    public function getGroup($courseId, $groupName)
    {
        try {
            $groups = $this->getCourseGroups($courseId);
            
            if ($groups && isset($groups['groups'])) {
                foreach ($groups['groups'] as $group) {
                    if ($group['name'] === $groupName) {
                        return $group;
                    }
                }
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create group in course
     *
     * @param int $courseId Course ID
     * @param string $groupName Group name
     * @return array|false
     */
    public function createGroup($courseId, $groupName)
    {
        try {
            $parameters = [
                'groups' => [
                    [
                        'courseid' => $courseId,
                        'name' => $groupName,
                    ],
                ],
            ];

            $json = $this->moodleRest->request('core_group_create_groups', $parameters);
            $json = json_decode($json, true);

            if (isset($json[0]['id'])) {
                return $json;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Helper method to handle API responses consistently
     *
     * @param string $json Raw JSON response
     * @param string $operation Operation name for error context
     * @return array
     * @throws ApiException
     */
    protected function handleApiResponse($json, $operation = 'API call')
    {
        $result = json_decode($json, true);
        
        if ($result === null) {
            throw new ApiException(
                "Failed to decode JSON response from {$operation}",
                ['raw_response' => $json, 'operation' => $operation]
            );
        }
        
        // Check for Moodle API errors
        if (isset($result['exception'])) {
            throw new ApiException(
                "Moodle API error in {$operation}: " . ($result['message'] ?? 'Unknown error'),
                [
                    'exception' => $result['exception'],
                    'errorcode' => $result['errorcode'] ?? null,
                    'message' => $result['message'] ?? null,
                    'operation' => $operation
                ]
            );
        }
        
        return $result;
    }
}
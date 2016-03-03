<?php

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Session\SessionHandler;

class DynamoDbSession
{
    /**
     * @var DynamoDbClient
     */
    protected $client;

    /**
     * @var string Name of DynamoDB table to store sessions in
     */
    protected $table;

    /**
     * @var SessionHandler
     */
    protected $handler;

    /**
     * Getter for SessionHandler
     *
     * @return SessionHandler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get an instance of DynamoDbSession configured from the environment if available.
     * @return null|DynamoDbSession
     */
    public static function get()
    {
        // Use DynamoDB for distributed session storage if it's configured
        if (defined('AWS_DYNAMODB_SESSION_TABLE') && AWS_DYNAMODB_SESSION_TABLE) {
            $dynamoOptions = array('region' => AWS_REGION_NAME);

            if (defined('AWS_ACCESS_KEY') && defined('AWS_SECRET_KEY')) {
                $dynamoOptions['key'] = AWS_ACCESS_KEY;
                $dynamoOptions['secret'] = AWS_SECRET_KEY;
            } else {
                // cache credentials when IAM fetches the credentials from EC2 metadata service
                // this will use doctrine/cache (included via composer) to do the actual caching into the filesystem
                // http://docs.aws.amazon.com/aws-sdk-php/guide/latest/performance.html#cache-instance-profile-credentials
                $dynamoOptions['credentials.cache'] = true;
            }

            return new static($dynamoOptions, AWS_DYNAMODB_SESSION_TABLE);
        }

        return null;
    }

    public function __construct($options, $table)
    {
        $this->client = DynamoDbClient::factory($options);
        $this->table = $table;
        $this->handler = SessionHandler::factory(array(
            'dynamodb_client' => $this->client,
            'table_name' => $this->table,
            'session_lifetime' => $this->getSessionLifetime(),
        ));
    }

    /**
     * check the AWS constant or refer to the Session class to find the session timeout value (if it exists) in terms
     * of DynamoDB, session_lifetime is the time to mark the inactive session to be garbage collected
     * if {@link GarbageCollectSessionCronTask} is running periodically on your server (via the silverstripe-crontask
     * module), then the inactive session will get removed from the DynamoDB session table.
     *
     * @return int The session lifetime
     */
    protected function getSessionLifetime() {
        if (defined('AWS_DYNAMODB_SESSION_LIFETIME')) {
            return AWS_DYNAMODB_SESSION_LIFETIME;
        }
        if (($timeout = (int)Config::inst()->get('Session', 'timeout')) > 0) {
            return $timeout;
        }
        return (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * Register DynamoDB as the session handler.
     */
    public function register()
    {
        return $this->handler->register();
    }

    /**
     * Garbage collect the configured DynamoDB session table
     */
    public function collect()
    {
        return $this->handler->garbageCollect();
    }
}

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

            return new DynamoDbSession($dynamoOptions, AWS_DYNAMODB_SESSION_TABLE);
        }

        return null;
    }

    public function __construct($options, $table)
    {
        $this->client = DynamoDbClient::factory($options);
        $this->table = $table;

        $handlerOptions = array(
            'dynamodb_client' => $this->client,
            'table_name' => $this->table,
        );

        if (defined('AWS_DYNAMODB_SESSION_LIFETIME')) {
            $handlerOptions['session_lifetime'] = AWS_DYNAMODB_SESSION_LIFETIME;
        }
        elseif (!isset($handlerOptions['session_lifetime'])) {
            $timeout = Config::inst()->get('Session', 'timeout');
            if ($timeout != null) {
                $handlerOptions['session_lifetime'] = $timeout;
            }
        }

        $this->handler = SessionHandler::factory($handlerOptions);
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

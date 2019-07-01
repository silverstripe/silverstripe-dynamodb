<?php

namespace SilverStripe\DynamoDb\Model;

use SilverStripe\DynamoDb\DynamoDbClient;
use SilverStripe\DynamoDb\SessionHandler;
use Aws\DoctrineCacheAdapter;
use Doctrine\Common\Cache\ApcuCache;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Session;
use SilverStripe\Core\Environment;

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
     *
     * @return null|DynamoDbSession
     */
    public static function get()
    {
        // Use DynamoDB for distributed session storage if it's configured
        $awsDynamoDBSessionTable = Environment::getEnv('AWS_DYNAMODB_SESSION_TABLE');
        if (!empty($awsDynamoDBSessionTable)) {
            $awsRegionName = Environment::getEnv('AWS_REGION_NAME');
            $awsDynamoDBEndpoint = Environment::getEnv('AWS_DYNAMODB_ENDPOINT');
            $awsAccessKey = Environment::getEnv('AWS_ACCESS_KEY');
            $awsSecretKey = Environment::getEnv('AWS_SECRET_KEY');

            $dynamoOptions = array('region' => $awsRegionName);

            // This endpoint can be set for locally testing DynamoDB.
            // see http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/DynamoDBLocal.html
            if (!empty($awsDynamoDBEndpoint)) {
                $dynamoOptions['endpoint'] = $awsDynamoDBEndpoint;
            }

            if (!empty($awsAccessKey) && !empty($awsSecretKey)) {
                $dynamoOptions['credentials']['key'] = $awsAccessKey;
                $dynamoOptions['credentials']['secret'] = $awsSecretKey;
            } else {
                // cache credentials when IAM fetches the credentials from EC2 metadata service
                // this will use doctrine/cache (included via composer) to do the actual caching into APCu
                // http://docs.aws.amazon.com/aws-sdk-php/guide/latest/performance.html#cache-instance-profile-credentials
                $dynamoOptions['credentials'] = new DoctrineCacheAdapter(new ApcuCache());
            }

            return new static($dynamoOptions, $awsDynamoDBSessionTable);
        }

        return null;
    }

    public function __construct($options, $table)
    {
        $this->client = new DynamoDbClient($table, array_merge(['version' => '2012-08-10'], $options));

        $this->table = $table;
        $this->handler = SessionHandler::fromClient(
            $this->client,
            [
            'table_name' => $this->table,
            'session_lifetime' => $this->getSessionLifetime(),
            ]
        );
    }

    /**
     * check the AWS constant or refer to the Session class to find the session timeout value (if it exists) in terms
     * of DynamoDB, session_lifetime is the time to mark the inactive session to be garbage collected
     * if {@link GarbageCollectSessionCronTask} is running periodically on your server (via the silverstripe-crontask
     * module), then the inactive session will get removed from the DynamoDB session table.
     *
     * @return int The session lifetime
     */
    protected function getSessionLifetime()
    {
        $awsDynamoDBSessionLifetime = Environment::getEnv('AWS_DYNAMODB_SESSION_LIFETIME');
        if (!empty($awsDynamoDBSessionLifetime)) {
            return $awsDynamoDBSessionLifetime;
        }
        if (($timeout = (int)Config::inst()->get(Session::class, 'timeout')) > 0) {
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
}

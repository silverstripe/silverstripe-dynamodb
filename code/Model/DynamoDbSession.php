<?php
namespace SilverStripe\DynamoDb\Model;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\SessionHandler;
use Aws\DoctrineCacheAdapter;
use Doctrine\Common\Cache\ApcuCache;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Session;
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
        $AWS_DYNAMODB_SESSION_TABLE = getenv('AWS_DYNAMODB_SESSION_TABLE');
        if (!empty($AWS_DYNAMODB_SESSION_TABLE)) {
            $AWS_REGION_NAME = getenv('AWS_REGION_NAME');
            $AWS_DYNAMODB_ENDPOINT = getenv('AWS_DYNAMODB_ENDPOINT');
            $AWS_ACCESS_KEY = getenv('AWS_ACCESS_KEY');
            $AWS_SECRET_KEY = getenv('AWS_SECRET_KEY');
            $dynamoOptions = array('region' => $AWS_REGION_NAME);
            // This endpoint can be set for locally testing DynamoDB.
            // see http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/DynamoDBLocal.html
            if (!empty($AWS_DYNAMODB_ENDPOINT)) {
                $dynamoOptions['endpoint'] = $AWS_DYNAMODB_ENDPOINT;
            }
            if (!empty($AWS_ACCESS_KEY) && !empty($AWS_SECRET_KEY)) {
                $dynamoOptions['credentials']['key'] = $AWS_ACCESS_KEY;
                $dynamoOptions['credentials']['secret'] = $AWS_SECRET_KEY;
            } else {
                // cache credentials when IAM fetches the credentials from EC2 metadata service
                // this will use doctrine/cache (included via composer) to do the actual caching into APCu
                // http://docs.aws.amazon.com/aws-sdk-php/guide/latest/performance.html#cache-instance-profile-credentials
                $dynamoOptions['credentials'] = new DoctrineCacheAdapter(new ApcuCache());
            }
            return new static($dynamoOptions, $AWS_DYNAMODB_SESSION_TABLE);
        }
        return null;
    }
    public function __construct($options, $table)
    {
        $this->client = new DynamoDbClient(array_merge(['version' => '2012-08-10'], $options));
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
        $AWS_DYNAMODB_SESSION_LIFETIME = getenv('AWS_DYNAMODB_SESSION_LIFETIME');
        if (!empty($AWS_DYNAMODB_SESSION_LIFETIME)) {
            return $AWS_DYNAMODB_SESSION_LIFETIME;
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

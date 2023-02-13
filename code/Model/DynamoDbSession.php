<?php

namespace SilverStripe\DynamoDb\Model;

use Aws\Credentials\CredentialProvider;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\SessionHandler;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Session;
use SilverStripe\Core\Environment;

class DynamoDbSession
{
    protected DynamoDbClient $client;

    /**
     * Name of DynamoDB table to store sessions in
     */
    protected string $table;

    protected SessionHandler $handler;

    public function getHandler(): SessionHandler
    {
        return $this->handler;
    }

    /**
     * Get an instance of DynamoDbSession configured from the environment if available.
     */
    public static function get(): ?static
    {
        // Use DynamoDB for distributed session storage if it's configured
        $awsDynamoDBSessionTable = Environment::getEnv('AWS_DYNAMODB_SESSION_TABLE');
        if (!empty($awsDynamoDBSessionTable)) {
            $awsRegionName = Environment::getEnv('AWS_REGION_NAME');
            $awsDynamoDBEndpoint = Environment::getEnv('AWS_DYNAMODB_ENDPOINT');
            $awsAccessKey = Environment::getEnv('AWS_ACCESS_KEY');
            $awsSecretKey = Environment::getEnv('AWS_SECRET_KEY');

            $dynamoOptions = ['region' => $awsRegionName];

            // This endpoint can be set for locally testing DynamoDB.
            // see http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/DynamoDBLocal.html
            if (!empty($awsDynamoDBEndpoint)) {
                $dynamoOptions['endpoint'] = $awsDynamoDBEndpoint;
            }

            if (!empty($awsAccessKey) && !empty($awsSecretKey)) {
                $dynamoOptions['credentials']['key'] = $awsAccessKey;
                $dynamoOptions['credentials']['secret'] = $awsSecretKey;
            } else {
                $dynamoOptions['credentials'] = CredentialProvider::defaultProvider();
            }

            return new static($dynamoOptions, $awsDynamoDBSessionTable);
        }

        return null;
    }

    public function __construct(array $options, string $table)
    {
        // For available client versions see https://docs.aws.amazon.com/aws-sdk-php/v3/api/index.html
        // It should always be fixed rather than "latest" to avoid breaking changes in the API specification.
        $this->client = new DynamoDbClient(array_merge(['version' => '2012-08-10'], $options));
        $this->table = $table;
        $this->handler = SessionHandler::fromClient(
            $this->client,
            [
                'table_name' => $this->table,
                'session_lifetime' => $this->getSessionLifetime(),
                'data_attribute_type' => 'binary'
            ]
        );
    }

    /**
     * Check the AWS constant or refer to the Session class to find the session timeout value (if it exists) in terms
     * of DynamoDB, session_lifetime is the time to mark the inactive session to be garbage collected.
     * If {@link GarbageCollectSessionCronTask} is running periodically on your server (e.g. via the silverstripe-crontask
     * module), then the inactive session will get removed from the DynamoDB session table.
     */
    protected function getSessionLifetime(): int
    {
        $awsDynamoDBSessionLifetime = Environment::getEnv('AWS_DYNAMODB_SESSION_LIFETIME');
        if (!empty($awsDynamoDBSessionLifetime)) {
            return (int) $awsDynamoDBSessionLifetime;
        }
        if (($timeout = (int) Config::inst()->get(Session::class, 'timeout')) > 0) {
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

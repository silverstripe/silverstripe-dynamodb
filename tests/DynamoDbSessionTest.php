<?php

namespace SilverStripe\DynamoDb\Tests;

use ReflectionProperty;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\DynamoDb\Model\DynamoDbSession;
use Aws\DynamoDb\DynamoDbClient;
use SilverStripe\Control\Session;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Cookie;
use Exception;

class DynamoDbSessionTest extends SapphireTest
{
    public function testGetReturnsNullWhenNotConfigured()
    {
        // Ensure the session table is null for this test
        $origTableName = Environment::getEnv('AWS_DYNAMODB_SESSION_TABLE');
        Environment::setEnv('AWS_DYNAMODB_SESSION_TABLE', null);

        $this->assertNull(DynamoDbSession::get());

        Environment::setEnv('AWS_DYNAMODB_SESSION_TABLE', $origTableName);
    }

    public function testGetReturnsSessionWhenConfigured()
    {
        // Ensure the session table is not null for this test
        $origTableName = Environment::getEnv('AWS_DYNAMODB_SESSION_TABLE');
        $origRegionName = Environment::getEnv('AWS_REGION_NAME');
        $origDynamoDBEndpoint = Environment::getEnv('AWS_DYNAMODB_ENDPOINT');
        $origAccessKey = Environment::getEnv('AWS_ACCESS_KEY');
        $origSecretKey = Environment::getEnv('AWS_SECRET_KEY');
        Environment::setEnv('AWS_DYNAMODB_SESSION_TABLE', 'sessiontable');
        Environment::setEnv('AWS_REGION_NAME', 'ap-southeast-2');
        Environment::setEnv('AWS_DYNAMODB_ENDPOINT', 'http://www.example.com/');
        Environment::setEnv('AWS_ACCESS_KEY', 'arbitrary-value');
        Environment::setEnv('AWS_SECRET_KEY', 'arbitrary-value');
        $session = DynamoDbSession::get();

        // Check there is a session
        $this->assertInstanceOf(DynamoDbSession::class, $session);

        $reflectionTable = new ReflectionProperty($session, 'table');
        $reflectionTable->setAccessible(true);

        // Check the session set the correct table name
        $this->assertSame('sessiontable', $reflectionTable->getValue($session));

        // Set env vars back to whatever they were before
        Environment::setEnv('AWS_DYNAMODB_SESSION_TABLE', $origTableName);
        Environment::setEnv('AWS_REGION_NAME', $origRegionName);
        Environment::setEnv('AWS_DYNAMODB_ENDPOINT', $origDynamoDBEndpoint);
        Environment::setEnv('AWS_ACCESS_KEY', $origAccessKey);
        Environment::setEnv('AWS_SECRET_KEY', $origSecretKey);
    }

    public function testGetSessionHandler()
    {
        $dynamoOptions['region'] = 'us-west-2';
        $dynamoOptions['key'] = 'AWS_ACCESS_KEY';
        $dynamoOptions['secret'] = 'AWS_SECRET_KEY';
        $sess = new DynamoDbSession($dynamoOptions, 'session_table');
        $handler = $sess->getHandler();
        $this->assertInstanceOf('\\Aws\\DynamoDb\\SessionHandler', $handler);
    }

    private function getSessions(DynamoDbClient $client, string $tableName): array
    {
        return $client->scan([
            'TableName' => $tableName,
        ])->get('Items');
    }

    /**
     * This test requires the strerr="true" attribute in phpunit.xml.dist to be set otherwise
     * you'll get a failure because of
     * "session_set_save_handler(): Session save handler cannot be changed after headers have already been sent"
     */
    public function testSessionsAreStoredInDynamoDB()
    {
        $tableName = 'unit-test-mysession';

        $dynamoDbSession = new DynamoDbSession([
            'endpoint' =>  Environment::getEnv('AWS_DYNAMODB_ENDPOINT'),
            'region' => Environment::getEnv('AWS_REGION_NAME'),
            'credentials' => [
                'key' => Environment::getEnv('AWS_ACCESS_KEY'),
                'secret' => Environment::getEnv('AWS_SECRET_KEY')
            ]
        ], $tableName);
        $dynamoDbSession->register();
        $client = $dynamoDbSession->getClient();

        $result = $client->listTables();
        // Delete any pre-existing session table (may still be there from failed unit test)
        foreach ($result['TableNames'] ?? [] as $resultTableName) {
            if ($resultTableName === $tableName) {
                $client->deleteTable([
                    'TableName' => $tableName,
                ]);
            }
        }

        // Create new session table
        $client->createTable([
            'TableName' => $tableName,
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'id',
                    'AttributeType' => 'S'
                ],
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'id',
                    'KeyType' => 'HASH'
                ]
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 1,
                'WriteCapacityUnits' => 1
            ],
        ]);

        $sessions = $this->getSessions($client, $tableName);
        $this->assertSame(0, count($sessions));

        // Start a new session in Silverstripe
        Session::config()->set('strict_user_agent_check', false);
        $req = new HTTPRequest('GET', '/');
        Cookie::set(session_name(), '1234');
        $session = new Session(null);
        $session->init($req);
        $sessionID = session_id();
        // Session has to close for the data to be written to dynamodb
        session_write_close();

        $sessions = $this->getSessions($client, $tableName);
        $this->assertSame(1, count($sessions));
        $this->assertStringContainsString($sessionID, $sessions[0]['id']['S']);

        // Delete the dynamodb table
        $client->deleteTable([
            'TableName' => $tableName,
        ]);
    }
}

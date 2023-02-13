<?php

namespace SilverStripe\DynamoDb\Tests;

use ReflectionProperty;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\DynamoDb\Model\DynamoDbSession;

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
}

<?php

class DynamoDbSessionTest extends \SapphireTest
{

    public function testGetReturnsNullWhenNotConfigured()
    {
        $this->assertNull(DynamoDbSession::get());
    }

    public function testGetSessionHandler()
    {
        $dynamoOptions['region'] = 'us-west-2';
        $dynamoOptions['key'] = 'AWS_ACCESS_KEY';
        $dynamoOptions['secret'] = 'AWS_SECRET_KEY';
        $sess = new DynamoDbSession($dynamoOptions, 'session_table');
        $handler = $sess->getHandler();
        $this->assertInstanceOf('\\Aws\\DynamoDb\\Session\\SessionHandler', $handler);
    }


}

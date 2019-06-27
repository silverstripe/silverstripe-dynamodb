<?php

namespace SilverStripe\DynamoDb;

use Aws\DynamoDb\SessionConnectionInterface;

/**
 * The only purpose of this class is to finish our custom
 * \SilverStripe\DynamoDb\DynamoDbClient initialization.
 * To do so it simply needs to pass session data attribute
 * from SessionConnection to DynamoDbClient::setSessionTableDataAttribute
 * so the latter may intercept session table updates intelligently.
 *
 * @see \SilverStripe\DynamoDb\DynamoDbClient
 */
class SessionHandler extends \Aws\DynamoDb\SessionHandler
{
    /**
     * @var SessionConnectionInterface
     */
    private $connection;

    public static function fromClient(\Aws\DynamoDb\DynamoDbClient $client, array $config = [])
    {
        $handler = parent::fromClient($client, $config);
        $client->setSessionTableDataAttribute($handler->connection->getDataAttribute());

        return $handler;
    }

    public function __construct(SessionConnectionInterface $connection)
    {
        $this->connection = $connection;
        parent::__construct($connection);
    }
}

<?php

namespace SilverStripe\DynamoDb;

/**
 * DynamoDbClient extension that handles sessions as binary strings rather than textual content.
 *
 * Current AWS SDK PHP (<= 3.102.1) implementation passes session data as 'S'.
 * This class overloads DynamoDbClient::updateItem method, intercepts the session table updates
 * and substitutes the data type from 'S' to 'B' (from string to binary).
 * PHP string is the php binary data type, so this should work seamlessly.
 *
 * @see https://github.com/silverstripe/silverstripe-dynamodb/issues/32
 *
 * @internal WARNING: this is not a part of the public API and will be removed in a patch release
 */
class DynamoDbClient extends \Aws\DynamoDb\DynamoDbClient
{
    /**
     * The name of the DynamoDB table where
     * sessions are stored
     *
     * @var string
     */
    private $sessionTable;

    /**
     * The name of the data attribute of the session table
     * where sessions are stored
     *
     * @var string
     */
    private $dataAttribute;

    /**
     * Initialize the client with the session table
     *
     * @param string $sessionTable The session table name
     *
     * {@inheritdoc}
     */
    public function __construct($sessionTable, ...$args)
    {
        $this->sessionTable = $sessionTable;
        parent::__construct(...$args);
    }

    /**
     * Initialize the client with the session data attribute (within the session table)
     * This method must be used to finish the client initialization, otherwise session updates will not be
     * intercepted and amended
     *
     * @param string $dataAttribute attribute of the session table where session data is persisted
     */
    public function setSessionTableDataAttribute($dataAttribute)
    {
        $this->dataAttribute = $dataAttribute;
    }

    public function updateItem($attributes, ...$extra)
    {
        $this->patchSessionUpdate($attributes);
        return parent::updateItem($attributes, ...$extra);
    }

    /**
     * Update the session data type from 'S' to 'B' (from string to binary)
     *
     * @param mixed &$data Data to be updated in-place
     */
    private function patchSessionUpdate(&$data)
    {
        if (!isset($data['TableName']) || $data['TableName'] !== $this->sessionTable) {
            return;
        }

        if (!isset($data['AttributeUpdates'][$this->dataAttribute]['Value']['S'])) {
            return;
        }

        $data['AttributeUpdates'][$this->dataAttribute]['Value']['B'] = $data['AttributeUpdates'][$this->dataAttribute]['Value']['S'];
        unset($data['AttributeUpdates'][$this->dataAttribute]['Value']['S']);
    }
}

<?php

use SilverStripe\DynamoDb\Model\DynamoDbSession;

$dynamoSession = DynamoDbSession::get();
if ($dynamoSession) {
    $dynamoSession->register();
}

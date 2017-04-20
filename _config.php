<?php
$dynamoSession = \SilverStripe\DynamoDb\Model\DynamoDbSession::get();
if($dynamoSession) {
	$dynamoSession->register();
}

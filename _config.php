<?php

$dynamoSession = DynamoDbSession::get();
if($dynamoSession) {
	$dynamoSession->register();
}

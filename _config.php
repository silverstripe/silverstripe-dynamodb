<?php

$dynamoSession = DynamoDbSession::get();
if($dynamoSession) {
	$dynamoSession->register();
}

if(defined('AWS_SYSLOG_LEVEL')) {
	$sysLogWriter = new SS_SysLogWriter();
	SS_Log::add_writer($sysLogWriter, (int)AWS_SYSLOG_LEVEL, '<=');
}

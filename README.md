# SilverStripe DynamoDB

This module enables storing SilverStripe sessions in DynamoDB.

## Installation

Add these custom repositories to your composer.json

	composer require silverstripe/dynamodb

## Sessions with DynamoDB

If you wish to store sessions in DynamoDB, set the following environment variables in your `_ss_environment.php` file:

	// the name of the DynamoDB table to store sessions in
	define('AWS_DYNAMODB_SESSION_TABLE', 'mysession');

	// the region that the DynamoDB table will live in (in this example here it uses Sydney)
	define('AWS_REGION_NAME', 'ap-southeast-2');

Once these are in place, this module will configure DynamoDB and register that as the session handler.
You will **need** to create the specified table using the AWS DynamoDB console for the region.

## Using DynamoDB outside of AWS

Sometimes you'll want to test that DynamoDB sessions work on your local development environment. You can make that
happen by defining `AWS_ACCESS_KEY` and `AWS_SECRET_KEY`. Please don't define these constants in the environment file
in EC2 instances, as credentials are automatically handled by the IAM role inside of AWS.

	// the AWS access key and secret. This is optional if you've configured an instance with an IAM role
	// http://docs.aws.amazon.com/aws-sdk-php/guide/latest/credentials.html#caching-iam-role-credentials
	define('AWS_ACCESS_KEY', '<access key here>');
	define('AWS_SECRET_KEY', '<access secret here>');

## Garbage collecting sessions

Inactive sessions are garbage collected by `GarbageCollectSessionCronTask` if [silverstripe-crontask](https://github.com/silverstripe-labs/silverstripe-crontask)
is setup on your instance. The time when a session should be collected after inactivity can be changed by setting
`Session::$timeout`.

For example, in your application's config YAML file, this sets a 20 minute session timeout:

	Session:
		timeout: 1200

You can also set the DynamoDB garbage collection time independently of `Session::$timeout`, but it's recommended you
make it at least the value of `Session::$timeout` or greater.

For example, in your `_ss_environment.php` file, set garbage collection after 1 hour of inactivity in sessions:

	define('AWS_DYNAMODB_SESSION_LIFETIME', 3600);

See http://docs.aws.amazon.com/aws-sdk-php/guide/latest/feature-dynamodb-session-handler.html for more information.



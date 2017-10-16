# SilverStripe DynamoDB

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-dynamodb.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-dynamodb)

This module enables storing SilverStripe sessions in DynamoDB.

## Requirements

 * SilverStripe 4.0+
 * PHP 5.6+

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

Before you can actually use this, you need to create a table in which to store the sessions. This can be done through the [AWS Console for Amazon DynamoDB](https://console.aws.amazon.com/dynamodb/home), or using the SDK. When creating the table, you should set the primary key to `id` of type `string`.

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

See https://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/dynamodb-session-handler.html for more information.

## Contribute

Do you want to contribute? Great, please see the [CONTRIBUTING.md](CONTRIBUTING.md)
guide.

## License

This module is released under the BSD 3-Clause License, see [license.md](license.md).

## Changelog

See the separate [CHANGELOG.md](CHANGELOG.md)

## Code of conduct

When having discussions about this module in issues or pull request please
adhere to the [SilverStripe Community Code of Conduct](https://docs.silverstripe.org/en/contributing/code_of_conduct).




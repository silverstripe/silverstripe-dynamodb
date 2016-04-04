# Changelog

Releases can be found at [https://github.com/silverstripe/silverstripe-dynamodb/releases](https://github.com/silverstripe/silverstripe-dynamodb/releases)

## v1.2.0 - 4 March 2016

This release fixes an issue where the session_lifetime for the dynamodb engine
was being ignored; falling back to php's `session.gc_maxlifetime` setting
instead.

Also, late static binding is used to instantiate the `DynamoDbSession` object for
better extensibility.

## v1.1.2 - 4 March 2016

Fixed issue where `session_lifetime` was not being assigned properly

## v1.1.0 - 24 November 2015

This release allows the code to get the underlying AWS session handler for
DynamoDb.

```
DynamoDbSession::get()->getHandler()
```

See the [AWS DynamoDB Session Handler](http://docs.aws.amazon.com/aws-sdk-php/v2/guide/feature-dynamodb-session-handler.html)
for more information.

## v1.0.2 - 24 November 2015

This release bumps the AWS PHP SDK to a version that allows session_id
regeneration.

## v1.0.1 - 8 July 2015

Remove logging configuration

## v1.0.0 - 2 July 2015

Initial release

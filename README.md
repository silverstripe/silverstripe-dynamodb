# SilverStripe DynamoDB

[![CI](https://github.com/silverstripe/silverstripe-dynamodb/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-dynamodb/actions/workflows/ci.yml)

This module enables storing SilverStripe sessions in DynamoDB.

## Requirements

* SilverStripe 5.0+
* PHP 8.1+

## Installation

Add these custom repositories to your composer.json

```bash
composer require silverstripe/dynamodb
```

## Sessions with DynamoDB

If you wish to store sessions in DynamoDB, set the required environment variables in `.env`:

```sh
# The name of the DynamoDB table to store sessions in
AWS_DYNAMODB_SESSION_TABLE=mysession

# The region that the DynamoDB table will live in (in this example here it uses Sydney)
AWS_REGION_NAME=ap-southeast-2
```

Once these are in place, this module will configure DynamoDB and register that as the session handler.

Before you can actually use this, you need to create a table in which to store the sessions.
Follow the instructions in the [AWS SDK for PHP documentation](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/service_dynamodb-session-handler.html#basic-usage).

**IMPORTANT: You need to [set up a TTL attribute](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/service_dynamodb-session-handler.html#ddbsh-garbage-collection) for garbage collection in your table.
The module does not provide automated garbage collection abilities.**

## Using DynamoDB outside of AWS

Sometimes you'll want to test that DynamoDB sessions work on your local development environment. You can make that
happen by defining `AWS_ACCESS_KEY` and `AWS_SECRET_KEY`. Please don't define these constants in the environment file
in EC2 instances, as credentials are automatically handled by the IAM role inside of AWS.

```sh
# The AWS access key and secret. This is optional if you've configured an instance with an IAM role
# https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html
# Note that AWS_ACCESS_KEY can only contain alphanumeric characters 
# https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/DynamoDBLocal.DownloadingAndRunning.html#DynamoDBLocal.DownloadingAndRunning.title
AWS_ACCESS_KEY=myaccesskey
AWS_SECRET_KEY=mysecret
```

## Local Testing

You can simulate DynamoDB locally for easier development through [DynamoDB Local](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/SettingUp.html).

Set environment constants. Note that actual access keys and regions are ignored,
they just need to be defined.

```bash
AWS_DYNAMODB_SESSION_TABLE=mysession
AWS_ACCESS_KEY=myaccesskey
AWS_SECRET_KEY=mysecret
AWS_DYNAMODB_ENDPOINT=http://localhost:8000
AWS_REGION_NAME=ap-southeast-2
```

Download [DynamoDB Local](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/SettingUp.html)
and start it - it'll be available under `http://localhost:8000`.

Now use the [AWS CLI Tools](https://docs.aws.amazon.com/cli/latest/userguide/cli-chap-install.html)
to interact with your local DynamoDB.

Configure user (optional):

You can configure a user to use with the AWS CLI tools. Use this if you are having issues with the environment variables being picked up.

```bash
aws configure set aws_access_key_id myaccesskey
aws configure set aws_secret_access_key myaccesskey
aws configure set default.region ap-southeast-2
```

Create table:

```bash
aws dynamodb create-table --table-name mysession --attribute-definitions AttributeName=id,AttributeType=S --key-schema AttributeName=id,KeyType=HASH --provisioned-throughput ReadCapacityUnits=1,WriteCapacityUnits=1 --endpoint-url http://localhost:8000
```

List tables:

```bash
aws dynamodb list-tables --endpoint-url http://localhost:8000
```

List all sessions:

```bash
aws dynamodb scan --table-name mysession --endpoint-url http://localhost:8000
```

Delete all sessions (use create table to reset afterwards):

```bash
aws dynamodb delete-table --table-name mysession --endpoint-url http://localhost:8000
```

## Contribute

Do you want to contribute? Great, please see the [CONTRIBUTING.md](CONTRIBUTING.md)
guide.

## License

This module is released under the BSD 3-Clause License, see [license.md](license.md).

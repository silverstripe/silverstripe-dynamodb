<?php
namespace SilverStripe\DynamoDb\Tasks;

use SilverStripe\Core\Object;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\DynamoDb\Model;

/**
 * Assuming there is DynamoDB session support registered,
 * this task will run a garbage collection of the sessions
 * stored in a configured DynamoDB table.
 *
 * Because this task could affect application performance,
 * it's recommended it be run in an off-peak time, such as
 * early in the morning.
 *
 * Default schedule is to run this at 3am each morning.
 * However, you can override this time by setting the
 * $schedule variable to something else using the {@link Config}
 * system.
 *
 * @see https://github.com/silverstripe-labs/silverstripe-crontask
 * @see http://docs.aws.amazon.com/aws-sdk-php/guide/latest/feature-dynamodb-session-handler.html
 */
class GarbageCollectSessionCronTask extends Object implements CronTask
{
    private static $schedule = '* * * * *';

    public function getSchedule()
    {
        return self::config()->schedule;
    }

    public function output($message)
    {
        if (PHP_SAPI === 'cli') {
            echo $message.PHP_EOL;
        } else {
            echo $message.'<br>'.PHP_EOL;
        }
    }

    public function process()
    {
        $dynamoSession = DynamoDbSession::get();
        if ($dynamoSession) {
            $dynamoSession->collect();
            $this->output('DynamoDB session garbage collection finished');
        } else {
            $this->output('DynamoDB session not enabled. Skipping');
        }
    }
}

<?php // version 8.0

function check_email($email)
{
    echo "CHECKING: {$email} \n";
}

function send_email($email, $from, $to, $subj, $body)
{
    echo "SENDING: {$email} \n";
}

const TEMPLATE = '{username}, your subscription is expiring soon';
const SUBJECT = 'Your subscription will expire soon';
$connection = mysqli_connect('127.0.0.1', 'root', '', 'mailing', '3306');

function getExpiringSoonSubscribers($daysToResubscribe = 3): array
{
    global $connection;
    $stack = [];
    $result = mysqli_query($connection, "
        SELECT
            users.id,
            users.username,
            users.email,
            emails.checked,
            emails.valid
        FROM
            users
            LEFT JOIN emails ON users.email = emails.email
        WHERE
            users.confirmed = 1 AND 
            users.notified = 0 AND 
            users.invalid = 0 AND 
            users.validts - INTERVAL {$daysToResubscribe} DAY < NOW()
        LIMIT 5
    ");
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $stack[] = $row;
    }
    return $stack;
}

function markAsNotified(int $id)
{
    global $connection;
    mysqli_query($connection, "UPDATE users SET notified = 1 WHERE id = {$id}");
}

function markAsInvalid(int $id)
{
    global $connection;
    mysqli_query($connection, "UPDATE users SET invalid = 1 WHERE id = {$id}");
}

$subscribers = getExpiringSoonSubscribers();
foreach ($subscribers as $subscriber) {
    if ($subscriber['checked']) {
        if ($subscriber['valid']) {
            $message = str_replace('{username}', $subscriber['username'], TEMPLATE);
            send_email(
                $subscriber['email'],
                'noreply@service.com',
                $subscriber['username'],
                SUBJECT,
                $message
            );
            markAsNotified($subscriber['id']);
        } else {
            markAsInvalid($subscriber['id']);
        }
    } else {
        check_email($subscriber['email']);
    }

}
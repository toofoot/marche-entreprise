<?php

include_once 'bootstrap.php';

$oQueue = new Aurel_Table_Queue();
$oUser = new Aurel_Table_User();

$config = Aurel_Table_Config::getConfig();

while ($queue = $oQueue->getOneReadyToSend()) {

    $subject = $queue->subject;
    $body = $queue->body;

    $link = "http://marche-entreprises.btob-adidas.com/compte/register?invitation=" . md5($queue->id_queue);

    $user = null;
    if ($queue->id_user) {
        $user = $oUser->getById($queue->id_user);
    }

    $mailSend = new Aurel_Mailer("utf-8");
    $mailSend->setBodyHtmlWithDesign($body, $subject, "utf-8", Zend_Mime::ENCODING_QUOTEDPRINTABLE, $config, $user)
        ->setFrom('contact@btob-adidas.com', $config->from_mail)
        ->setSubject($subject)
        ->addTo($queue->to);
    try {
        echo $queue->to . ":" . $subject;
        $test = $mailSend->send();

        $queue->status = Aurel_Table_Queue::STATUS_SENT;
        $queue->date_sent = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    } catch (Exception $e) {

        $queue->state = Aurel_Table_Queue::STATUS_INIT;
    }
    $queue->save();
}

<?php

include_once 'bootstrap.php';

$oInvitation = new Aurel_Table_Invitation();
$oUser = new Aurel_Table_User();

$config = Aurel_Table_Config::getConfig();
    
while ($invitation = $oInvitation->getOneReadyToSend()) {
    
    $inviteur = $oUser->getById($invitation->id_user_creation);
    $subject = $config->subject_invitation;
    $body = $config->body_invitation;
    
    $link = "http://marche-entreprises.btob-adidas.com/compte/register?invitation=" . md5((string) $invitation->id_invitation);
    $replacement = [
        '#INVITEUR_PRENOM#' => $inviteur->firstname,
        '#INVITEUR_NOM#' => $inviteur->lastname,
        '#INVITEUR_SOCIETE#' => $inviteur->societe,
        '#INVITEUR_EMAIL#' => $inviteur->email,
        '#INVITEUR_FONCTION#' => $inviteur->fonction,
        '#INVITE_EMAIL#' => $invitation->email,
        '#INVITE_MESSAGE#' => $invitation->message,
        '#LIEN#' => $link,
    ];
    
    foreach($replacement as $key => $value){
        $subject = str_replace($key, $value, (string) $subject);
        $body = str_replace($key, $value, (string) $body);
    }
    

    $mailSend = new Aurel_Mailer("utf-8");
    $mailSend->setBodyHtmlWithDesign($body, $subject)
            ->setFrom('contact@btob-adidas.com', $config->from_mail)
            ->setSubject($subject)
            ->addTo($invitation->email);
    try {
        echo $invitation->email . ":" . $subject;
        $test = $mailSend->send();
        
        $invitation->state = Aurel_Table_Invitation::TYPE_SENT;
        $invitation->date_sent = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    } catch (Exception) {
        
        $invitation->state = Aurel_Table_Invitation::TYPE_INIT;
    }
    $invitation->save();
}
?>

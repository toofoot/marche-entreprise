<?php 
	include_once 'bootstrap.php';
	
	$oAnnonces = new Aurel_Table_Article();
	$annonces = $oAnnonces->getAllAnnoncesLimit($config->daysArchiveAnnonce);
	$date = new Aurel_Date();
	$date->setTime("00:00");
	
	$mailUsers = array();
	foreach($annonces as $annonce){
		$dateArchive = $annonce
		->getDate('date_validation')
		->addDay($config->daysArchiveAnnonce)
		->setTime("00:00");
		
		$diff = $dateArchive->sub($date)->toString(Zend_Date::DAY) - 1;
		if($diff == 1){
			$mailUsers[$annonce->email][] = $annonce;
		}
	}
	
	foreach($mailUsers as $mail=>$annonces){
		$nbAnnonces = count($annonces);
		
		$subject = "Expiration de vos annonces";
		
		$body = "";
		$body .= "Bonjour,\n";
		if($nbAnnonces == 1){
			foreach($annonces as $annonce){
				$titleAnnonce = $annonce->title;
				$IdAnnonce = $annonce->id_article;
			}
			$body .= "Votre annonce n°$IdAnnonce ($titleAnnonce) expire demain, vous pouvez la renouveler en vous connectant et en accédant à la page de gestion de vos annonces\n\n";
		} else {
			$body .= "$nbAnnonces annonces expirent demain, vous pouvez les renouveler en vous connectant et en accédant à la page de gestion de vos annonces\n";
			$body .= "Voici les annonces concernées :\n";
			$body .= "<ul>";
			foreach($annonces as $annonce){
				$body .= "<li>Annonce n°{$annonce->id_article} ({$annonce->title})</li>";
			}
			$body .= "</ul>\n\n";
		}
		$body .= "<a href='http://www.lepetitcharsien.com/compte/annonces#cours'>GERER MES ANNONCES</a>";
		
		$mailSend = new Aurel_Mailer("utf-8");
		$mailSend->setBodyHtmlWithDesign($body,$subject)
		->setSubject($subject)
		->addTo($mail)
		->setFrom("no-reply@lepetitcharsien.com","Le Petit Charsien");
		try{
			echo $mail . ":" . $subject;
			$mailSend->send();
		} catch(Exception $e){
			Zend_Debug::dump($e);
		}
	}
?>
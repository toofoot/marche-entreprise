<?php 
	include_once 'bootstrap.php';

    $oArticle = new Aurel_Table_Article();
    $articles = $oArticle->getAll(null,null,true);

    $entries = array();
    foreach($articles as $article){
        $route = $article->annonce ? "article" : "annonce";
        $date = new Aurel_Date($article->date_creation);

        $entry = array(
            'title'       => $article->title,
            'link'        => "http://www.lepetitcharsien.com/{$route}/{$article->basename}",
            'description' => substr(strip_tags($article->content),0, 300),
            'lastUpdate'  => $date->get(Aurel_Date::TIMESTAMP)
        );

        array_push($entries, $entry);
    }

    $rss = array(
        'title'   => "Le Petit Charsien",
        'link'    => "http://www.lepetitcharsien.com",
        'description'   => "",
        //'description'   => $config->site->description,
        'charset' => "UTF-8",
        'language'    => 'fr',
        'entries' => $entries
    );

    $feed = Zend_Feed::importArray($rss, 'rss');
    $rssFeed = $feed->saveXML();

    $fh = fopen(BASE_PATH . "/www/flux.xml", "w");
    fwrite($fh, $rssFeed);
    fclose($fh);
?>
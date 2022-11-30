<?php
include_once 'bootstrap.php';

$oAnnonces = new Aurel_Table_Article();
$articles = $oAnnonces->fetchAll();

foreach($articles as $article){
    $content = $article->content;
    Zend_Debug::dump($content);

    $content = strip_tags((string) $content);
    $content = html_entity_decode($content);
    $content = htmlspecialchars_decode($content,ENT_QUOTES);
    $content = trim(preg_replace('/\s\s+/', ' ', $content));
    $content = utf8_encode($content);

    $article->content_extract = $content;
    $article->save();

}


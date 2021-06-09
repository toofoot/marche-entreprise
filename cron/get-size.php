<?php
include_once 'bootstrap.php';

$oPhotos = new Aurel_Table_Photo();
$upload_path = '../www/images/upload';
$photos = $oPhotos->getAll();
foreach($photos as $photo){
    if($photo->id_article){
        $dir = $upload_path . "/" . $photo->id_article . "/" . $photo->id_photo . "." . $photo->extension;
    } else {
        $dir = $upload_path . "/fiche_" . $photo->id_annuaire_fiche . "/" . $photo->id_photo . "." . $photo->extension;
    }
    $size = getimagesize($dir);
    $photo->width = $size[0];
    $photo->height = $size[1];
    $photo->save();
}
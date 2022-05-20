<?php
/**
* Classe user
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Newsletter extends Aurel_Table_Abstract 
{
	
	protected $_name = 'newsletter';
	protected $_rowClass = 'Aurel_Table_Row_Newsletter';

    public const STATUS_INIT = 0;
    public const STATUS_TOSENDADMIN = 1;
    public const STATUS_TOSENDMEMBERS = 2;
    public const STATUS_SENT = 3;

	/**
	* Genere un mot de passe de 8 caractères aléatoirement
	* @return string
	*/
	public function generePassword ()
	{
		// On définit le password à blanc
		$password = "";
		// on définit les caractères possibles
		$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ"; 
		// compteur
		$i = 0; 
		// on choisis au hasard un des caractères et on l'ajoute à password 8 fois
		while ($i < 8) { 
			// On choisis un chiffre au hasard et on regarde le caractere correspondant dans $possible
			$nbr = random_int (0,49);
			$char = $possible[$nbr];
			// si ce caractère est déjà dans le password, on ne l'insere pas
			if (!strstr($password, $char))
			{ 
				$password .= $char;
				$i++;
			}
		}
		// On a fini, on enregistre et on retourne le password
		return $password;
	}
	
	public function getAll(){
		$select = $this->select()
		->order('date_envoi DESC');
		
		return $this->fetchAll($select);
	}
	
	public function getAllArchived($archived = 1){
		$select = $this->select()
		->where('archived = ?',$archived)
		->order('date_envoi DESC');
		
		return $this->fetchAll($select);
	}
	
	public function getLastNewsletter(){
		$select = $this->select()
		->where('archived = ?',1)
		->order('date_envoi DESC')
		->limit(1);
		
		return $this->fetchRow($select);
	}

    public function getLastNewsletterWithArticles(){
		$select = $this->select()
		->where('articles = ?',1)
		->where('archived = ?',1)
		->order('date_envoi DESC')
		->limit(1);

		return $this->fetchRow($select);
	}

    public function getLastNewsletterWithAnnonces(){
		$select = $this->select()
		->where('annonces = ?',1)
		->where('archived = ?',1)
		->order('date_envoi DESC')
		->limit(1);

		return $this->fetchRow($select);
	}
	
	public function genereNewsletter($register = false, $texte = null, $texte2 = null, $articles = true, $annonces = true, $subject = null, $from = null)
    {
        $oMenu = new Aurel_Table_Menu();

        $oArticle = new Aurel_Table_Article();

        $articlesBool = $articles;
        $annoncesBool = $annonces;

        if ($articles) {
            $articlesAPublier = $oArticle->getJ7Articles();
            $tabArticles = array();
            foreach ($articlesAPublier as $article) {
                $tabArticles[$article->id_menu][$article->id_sous_menu][] = $article;
            }
        }

        if ($annonces) {
            $annoncesAPublier = $oArticle->getJ7Annonces();
            $tabAnnonces = array();
            foreach ($annoncesAPublier as $article) {
                $tabAnnonces[$article->id_menu][$article->id_sous_menu][] = $article;
            }
        }

        $menus = $oMenu->getAll();
        $arrayHtml = array();
        $arrayHtmlAnnonces = array();
        foreach ($menus as $menu) {
            if ($articles) {
                $arrayHtml[$menu->name]['articles'] = array();
                if (isset($tabArticles[$menu->id_menu])) {
                    $arrayHtml[$menu->name]['articles'] = $tabArticles[$menu->id_menu][null];
                }
            }
            if ($annonces) {
                $arrayHtmlAnnonces[$menu->name]['articles'] = array();
                if (isset($tabAnnonces[$menu->id_menu])) {
                    $arrayHtmlAnnonces[$menu->name]['articles'] = $tabAnnonces[$menu->id_menu][null];
                }
            }
            if ($menu->sous_menus_id) {
                $liste_basename = explode(",", $menu->sous_menus_basename);
                $liste_name = explode(",", $menu->sous_menus_name);
                $liste_id = explode(",", $menu->sous_menus_id);
                $liste_id_creation = explode(",", $menu->id_creation);

                foreach ($liste_id as $key => $id_sous_menu) {
                    if ($articles) {
                        if (isset($tabArticles[null][$id_sous_menu])) {
                            $arrayHtml[$menu->name]['sous_menus'][$liste_name[$key]] = $tabArticles[null][$id_sous_menu];
                        }
                    }
                    if ($annonces) {
                        if (isset($tabAnnonces[null][$id_sous_menu])) {
                            $arrayHtmlAnnonces[$menu->name]['sous_menus'][$liste_name[$key]] = $tabAnnonces[null][$id_sous_menu];
                        }
                    }
                }
            }
        }
        if ($articles) {
            foreach ($arrayHtml as $key => $array1) {
                if (count($array1['articles']) == 0)
                    unset($arrayHtml[$key]['articles']);
            }
            foreach ($arrayHtml as $key => $array1) {
                if (count($array1) == 0)
                    unset($arrayHtml[$key]);
            }
        }
        if ($annonces) {
            foreach ($arrayHtmlAnnonces as $key => $array1) {
                if (count($array1['articles']) == 0)
                    unset($arrayHtmlAnnonces[$key]['articles']);
            }
            foreach ($arrayHtmlAnnonces as $key => $array1) {
                if (count($array1) == 0)
                    unset($arrayHtmlAnnonces[$key]);
            }
        }

        $date = new Zend_Date();
        if (!$subject) {
            $texteDate = "Votre Newsletter du " . $date->get(Aurel_Date::DATE_LONG);
            $texteDateHtml = "Votre Newsletter du <strong>" . $date->get(Aurel_Date::DATE_LONG) . "</strong>";
        } else {
            $texteDate = $subject;
            $texteDateHtml = $subject;
        }
		
		$html = "<table style='width:650px;font-size:14px' align='center' cellspacing='0' cellpadding='0' border='0'>
				<tbody>
				<tr>
					<td colspan='5' style='background-color:#f3f3f3;font-size:10px;color:#999' align='center'>
					Vous recevez cet email car vous êtes inscrit sur lepetitcharsien.com. Si vous souhaitez vous désinscrire, <a href='http://{$_SERVER["HTTP_HOST"]}/compte/desinscription?unsubscribe=#emailEncoded#'>cliquez ici</a>.<br/>
					<a href='http://{$_SERVER["HTTP_HOST"]}/newsletter/#id_newsletter#?user=#emailEncoded#'>Consultez notre version en ligne.</a>
					</td>
				</tr>
				</tr>
					<td colspan='5' style='background-color:#f3f3f3' align='center'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/header.png' height='142' width='650' alt='Header' /></td>
				</tr>
				<tr>
					<td colspan='5' style='height:40px;width:650px;color:#323B8C;text-align:center;font-size:16px;vertical-align:middle;background-color:#fcb525'>{$texteDateHtml}</td>
				</tr>
				<tr>
					<td colspan='2' width='25' height='16' style='line-height:1px;height:16px;width:25px'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/top-leftv2.png' height='16' width='25' alt='TopRight' /></td>
					<td width='600' height='16' style='background-color:#fff;line-height:1px;height:16px;width:600px'></td>
					<td colspan='2' width='25' height='16' style='line-height:1px;height:16px;width:25px'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/top-rightv2.png' height='16' width='25' alt='TopLeft' /></td>
				</tr>
				<tr>
					<td width='16' style='background-color:#fcb525'></td>
					<td width='9' style='background-color:#fff;'></td>
					<td width='600' style='background-color:#fff;'>#BODY#</td>
					<td width='9' style='background-color:#fff;'></td>
					<td width='16' style='background-color:#fcb525'></td>
				</tr>
				<tr>
					<td width='16' style='background-color:#fcb525'></td>
					<td colspan='3' align='center' style='background-color:#fff;'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/bottomv2.png' alt='bottom' height='211' width='615' /></td>
					<td width='16' style='background-color:#fcb525'></td>
				</tr>
				<tr>
					<td colspan='2' width='25' height='16' style='line-height:1px;height:16px;width:25px'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/bottom-leftv2.png' height='16' width='25' alt='TopRight' /></td>
					<td width='600' height='16' style='background-color:#fff;line-height:1px;height:16px;width:600px'></td>
					<td colspan='2' width='25' height='16' style='line-height:1px;height:16px;width:25px'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/bottom-rightv2.png' height='16' width='25' alt='TopLeft' /></td>
				</tr>
				<tr>
					<td colspan='5' style='height:16px;width:650px;color:#323B8C;text-align:center;font-size:16px;vertical-align:middle;background-color:#fcb525'></td>
				</tr>
				</tbody>
			</table>
			<table style='width:650px;font-size:10px;color:#999' align='center' cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td align='center' style='font-size:10px;color:#999'>Vous recevez cet e-mail car vous êtes inscrit sur lepetitcharsien.com. Conformément à la loi Informatique et Libertés, vous disposez d'un droit d'accès et de rectification des données vous concernant. Si vous souhaitez vous désinscrire, <a href='http://{$_SERVER["HTTP_HOST"]}/compte/desinscription?unsubscribe=#emailEncoded#'>cliquez ici</a></td>
				</tr>
			</table>
		";
		
		$host = $_SERVER["HTTP_HOST"];
		if(!$texte)
			$texte = "<div style='text-align:center;'><span style='color:#323B8C'>Vous trouverez ci-dessous les liens vers les derniers articles mis en ligne sur <a style='color:#323B8C' href='http://$host'>$host</a>.<br/>Bonne lecture. La rédaction</span></div>";
		
		$body = "<div>";
		$body .= "<div style='padding-bottom:15px;color:#323B8C'>{$texte}</div>";
		
		if($texte2)
			$body .= "<div style='padding-bottom:15px;color:#323B8C'>{$texte2}</div>";
		
		if($articles && isset($arrayHtml) && !empty($arrayHtml)){
			$body .= "<h4 style='padding:5px;color:#323B8C;border-bottom:2px solid #323B8C'>ARTICLES</h4>";
			foreach($arrayHtml as $menu_name => $array){
				if(isset($array['articles'])){
					$body .= "<div style='width:500px;border-bottom:1px solid #323B8C'><div style='width:300px;background-color:#D8DAE9;color:#323B8C;font-weight:bold;padding:0 10px'>{$menu_name}</div></div>";
					$body .= "<ul style='margin:0 10px;padding:0;list-style-position:inside;'>";
					foreach($array['articles'] as $article){
						$body .= "<li><a style='color:#323B8C' href='http://{$_SERVER["HTTP_HOST"]}/article/{$article->basename}'>{$article->title}</a></li>";
					}
					$body .= "</ul>";
				} elseif(isset($array['sous_menus'])) {
					foreach($array['sous_menus'] as $sous_menus_name => $articles){
						
						$body .= "<div style='border-bottom:1px solid #323B8C'><div style='width:300px;background-color:#D8DAE9;color:#323B8C;font-weight:bold;padding:0 10px'>{$sous_menus_name}</div></div>";
						$body .= "<ul style='margin:0 10px 10px 10px;padding:0;list-style-position:inside;'>";
						foreach($articles as $article){
							$title = ucfirst(strtolower($article->title));
							$body .= "<li style='color:#F7931E'><a style='color:#323B8C;text-decoration:none' href='http://{$_SERVER["HTTP_HOST"]}/article/{$article->basename}'>{$title}</a></li>";
						}
						$body .= "</ul>";
					}
				}
				$body .= "<div style='text-align:center'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/separation.png' /></div>";
			}
		}
		if($annonces && isset($arrayHtmlAnnonces) && !empty($arrayHtmlAnnonces)){
			$body .= "<h4 style='padding:5px;color:#333;border-bottom:2px solid #333'>PETITES ANNONCES</h4>";
			foreach($arrayHtmlAnnonces as $menu_name => $array){
				if(isset($array['articles'])){
					$body .= "<div style='width:500px;border-bottom:1px solid #f8a899'><div style='width:300px;background-color:#f8a899;color:#f1421d;font-weight:bold;padding:0 10px'>{$menu_name}</div></div>";
					$body .= "<ul style='margin:0 10px;padding:0;list-style-position:inside;'>";
					foreach($array['articles'] as $article){
						$body .= "<li style='color:#f1421d'><a style='color:#333' href='http://{$_SERVER["HTTP_HOST"]}/annonce/{$article->basename}'>{$article->title}</a></li>";
					}
					$body .= "</ul>";
				} elseif(isset($array['sous_menus'])) {
					foreach($array['sous_menus'] as $sous_menus_name => $articles){
		
						$body .= "<div style='border-bottom:1px solid #f8a899'><div style='width:300px;background-color:#f8a899;color:#333;font-weight:bold;padding:0 10px'>{$sous_menus_name}</div></div>";
						$body .= "<ul style='margin:0 10px 10px 10px;padding:0;list-style-position:inside;'>";
						foreach($articles as $article){
							$title = ucfirst(strtolower($article->title));
							$body .= "<li style='color:#f1421d'><a style='color:#333;text-decoration:none' href='http://{$_SERVER["HTTP_HOST"]}/annonce/{$article->basename}'>{$title}</a></li>";
						}
						$body .= "</ul>";
					}
				}
				$body .= "<div style='text-align:center'><img src='http://{$_SERVER["HTTP_HOST"]}/images/newsletter/separation.png' /></div>";
			}
		}
		$body .= "</div>";
		
		$html = str_replace("#BODY#", $body, $html);

		$new = $this->createRow();
		$new->subject = $texteDate;
		$new->body = $html;
		$new->archived = 0;
		$new->ready_to_send = self::STATUS_TOSENDADMIN;
		$new->texte1 = $texte;
		$new->texte2 = $texte2;
		$new->articles = $articlesBool;
		$new->annonces = $annoncesBool;
        if(!$from){
            $new->from = 'redaction@lepetitcharsien.com';
        } else {
            $new->from = $from;
        }
		
		if($register){
			$new->save();
			
			$html = str_replace("#id_newsletter#", $new->id_newsletter, $html);
			$new->body = $html;
			$new->save();
		}
		
		return $new;
	}
}
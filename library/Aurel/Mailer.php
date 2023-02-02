<?php

class Aurel_Mailer extends Zend_Mail
{

    protected $_html;

    public function setBodyHtml($html, $charset = "utf-8", $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($charset === null) {
            $charset = $this->_charset;
        }
        $body = $html;

        $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
        $html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
        $html .= "<head>\n";
        $html .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
        $html .= "<meta content=\"width=device-width\">\n";
        $html .= "</head>\n";
        $html .= "<body style=\"font-family: Arial, Helvetica, Geneva, sans-serif;background-color: #f3f3f3;margin:0px; padding:0px; -webkit-text-size-adjust:none;\">\n";
        $html .= $body;
        $html .= "</body>\n";
        $html .= "</html>\n";

        $this->_html = $html;

        $mp = new Zend_Mime_Part($html);
        $mp->encoding = $encoding;
        $mp->type = Zend_Mime::TYPE_HTML;
        $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
        $mp->charset = $charset;

        $this->_bodyHtml = $mp;

        return $this;
    }

    public function setBodyHtmlWithDesign($html, $subject, $charset = "utf-8", $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE, $config = null, $user = null)
    {
        if ($charset === null) {
            $charset = $this->_charset;
        }
        $host = $_SERVER["HTTP_HOST"] ?? "www.lepetitcharsien.com";
        $body = $html;

        $hash = null;
        if ($user) {
            $hashing = Aurel_Encryptor::getInstance();
            $hashing->setDecryptedValue($user->email);
            $hashing->setExpirySeconds(20_000_000);
            $hashing->encrypt();

            $hash = $hashing->getEncryptedValue();
            $footer_email = $config ? $config->footer_email : null;
        } else {
            $footer_email = $config ? $config->footer_email_invitation : null;
        }


        $footer_email = str_replace('#HASH#', (string) $hash, (string) $footer_email);
        $body = str_replace('#HASH#', (string) $hash, (string) $body);

        $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
        $html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
        $html .= "<head>\n";
        $html .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
        $html .= "<meta content=\"width=device-width\">\n";
        $html .= "</head>\n";
        $html .= "<body style=\"font-family: Arial, Helvetica, Geneva, sans-serif;background-color: #f3f3f3;margin:0px; padding:0px; -webkit-text-size-adjust:none;\">\n";
        $html .= "<table style='width:650px;font-size:14px' align='center' cellspacing='0' cellpadding='0' border='0'>
				<tbody>
				<tr>
                                    <td style='height:40px;color:#fff;text-align:center;font-size:16px;vertical-align:middle;background-color:#000'> 
                                        <img src=\"http://marche-entreprises.btob-adidas.com/images/logo_adidas.png\" alt=\"Logo adidas\" /> 
                                    </td>
                                    <td style='height:40px;width:600px;color:#fff;text-align:center;font-size:16px;vertical-align:middle;background-color:#000'>
                                        
                                    </td>
				</tr>
				
				<tr>
                                    <td colspan='2' width='650' style='background-color:#fff;padding:15px;'>
                                    <span style='font-size:18px;font-weight:bold'>{$subject}</span><br><br>
                                    " . nl2br($body) . "
                                    </td>
				</tr>
                {$footer_email}
				</tbody>
			</table>
		";
        $html .= "</body>\n";
        $html .= "</html>\n";

        $this->_html = $html;

        $mp = new Zend_Mime_Part($html);
        $mp->encoding = $encoding;
        $mp->type = Zend_Mime::TYPE_HTML;
        $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
        $mp->charset = $charset;

        $this->_bodyHtml = $mp;

        return $this;
    }

    public function getHtml()
    {
        return $this->_html;
    }
}

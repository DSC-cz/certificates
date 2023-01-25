<?php

namespace Certificates;

if(empty($_GET['c_page'])) $_GET['c_page'] = 1;

class Shortcodes extends Database{
    protected $db;

    public function __construct($db){
        $this->db = $db;
        add_shortcode('certificates_form', array($this, 'certificates_form'));
        add_shortcode('certificates', array($this, 'certificates'));
        add_shortcode('certificate', array($this, 'certificate'));
    
		/*if ( ! wp_script_is( 'jquery', 'enqueued' )) {
			wp_enqueue_script("jquery", "https://code.jquery.com/jquery-3.6.0.min.js");
		}*/
	}

    protected function input($type, $name, $placeholder = null, $value = null, $class = "form-control", $accept = null){
        return "<input type=\"$type\" name=\"$name\" ".(!empty($placeholder) ? "placeholder=\"$placeholder\"" : "").(!empty($value) ? "value=\"$value\"" : "")." class=\"$class\" ".(!empty($accept) ? "accept=\"$accept\"" : "")."/>";

    }

    public function certificates_form(){
        $form = "<form action=\"#captcha\" id=\"captcha\" method=\"POST\" enctype=\"multipart/form-data\">";

        $form.=$this->input("text", "c_ico", "IČO*", (isset($_POST['c_ico']) ? $_POST['c_ico'] : ""));
        $form.=$this->input("submit", "c_findbyico", null, "Vyhledat", "btn btn-secondary ico");
        $form.='</br>';
        $form.=$this->input("text", "c_nazev", "Název firmy*", (isset($_POST['c_nazev']) ? $_POST['c_nazev'] : ""));
        $form.=$this->input("text", "c_okres", "Okres*", (isset($_POST['c_okres']) ? $_POST['c_okres'] : ""));
        $form.=$this->input("text", "c_obec", "Obec*", (isset($_POST['c_obec']) ? $_POST['c_obec'] : ""));
        $form.=$this->input("text", "c_adresa", "Adresa firmy*", (isset($_POST['c_adresa']) ? $_POST['c_adresa'] : ""));
		$form.=$this->input("text", "c_web", "Webová stránka firmy*", (isset($_POST['c_web']) ? $_POST['c_web'] : ""));
		$form.=$this->input("text", "c_obor", "Hlavní obor podnikání*", (isset($_POST['c_obor']) ? $_POST['c_obor'] : ""));
        $form.=$this->input("number", "c_pocet_zamestnancu", "Počet zaměstnanců*", (isset($_POST['c_pocet_zamestnancu']) ? $_POST['c_pocet_zamestnancu'] : ""));
		$form.=$this->input("number", "c_pocet_nekuraku", "Počet nekuřáků*", (isset($_POST['c_pocet_nekuraku']) ? $_POST['c_pocet_nekuraku'] : ""));
        $form.="<br/><label><strong>Obrázek firmy:</strong> </label>";
        $form.=$this->input("file", "c_image", null, null, null, "image/jpg, image/jpeg, image/png");
        $form.='<br/>';
        $form.=$this->input("text", "c_kontaktni_osoba", "Kontaktní osoba*", (isset($_POST['c_kontaktni_osoba']) ? $_POST['c_kontaktni_osoba'] : ""));
        $form.=$this->input("number", "c_telefon", "Telefonní číslo*", (isset($_POST['c_telefon']) ? $_POST['c_telefon'] : ""));
        $form.=$this->input("email", "c_email", "Email*", (isset($_POST['c_email']) ? $_POST['c_email'] : ""));
		$form.="<input id=\"c_certifikat\" name=\"c_certifikat\" value=\"on\" type=\"checkbox\" /> <label for=\"c_certifikat\">Mám zájem o certifikát</label><br/><input id=\"c_parnet\" name=\"c_partner\" value=\"on\" type=\"checkbox\" /> <label for=\"c_partner\">Mám zájem stát se partnerem projektu</label>";
		$form.="<div class=\"captcha\"></div>";
        $form.=$this->input("submit", "c_send", null, "Odeslat", "btn btn-primary");

        return $form.'</form><script src="'.(plugins_url().'/certificates/js/form.js?v=3').'" defer></script>';
    }

    public function certificates($atts = [], $content = null, $tag = ''){
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );

        $settings = shortcode_atts(
            array(
                'limit' => 9,
                'mainpage' => "false"
            ), $atts, $tag
        );

        try{
            $data = $this->select($this->db->prefix.'certificates', $settings['mainpage'], $settings['limit']);
            
            $r = '<table class="table table-striped"><thead><tr><th>Název firmy</th><th>Certifikát</th></tr></thead><tbody>';
			$certifikat = ["zlaty"=>"Zlatý", "stribrny"=>"Stříbrný", "bronzovy"=>"Bronzový"];
            foreach($data as $item){
                //if(!empty($item->fotka)) $r.= '<img src="'.$item->fotka.'" alt="Logo" />';
                
                $r.= '<tr><td><a href="/certifikat-firmy/?c_info='.$item->ico.'">'.$item->nazev.'</a></td>';
                $r.= '<td>'.$certifikat[$item->certifikat].' certifikát</td></tr>';
            }
			$r.="</tbody></table>";

            if($settings['mainpage'] == "false"){
                $c_num = $this->getnum($this->db->prefix.'certificates', $settings['mainpage']);
                $pages = ceil($c_num/$settings['limit']);

                $r.="Stránka ".$_GET['c_page'].' z '.$pages.'<br/>';
                if($pages == 1) return $r;

				if($_GET['c_page'] > 4) $r.='<a href="?c_page=1" class="btn btn-danger">1</a> ... ';

				for($i = $_GET['c_page'] - 3; $i < $_GET['c_page']; $i++){
					if($i < 1) continue;
					$r.='<a href="?c_page='.$i.'" class="btn btn-danger">'.$i.'</a> ';
				}

				for($i = $_GET['c_page']; $i < $_GET['c_page'] + 4; $i++){
					if($i > $pages) break;
					$r.='<a href="?c_page='.$i.'" class="btn '.($i == $_GET['c_page'] ? 'btn-dark' : 'btn-danger').'">'.$i.'</a> ';
				}
				if($pages - $_GET['c_page'] > 3) $r.='... <a href="?c_page='.$pages.'" class="btn btn-danger">'.$pages.'</a>';
            }

            return $r;

        } catch (\Exception $e){
            return "<div class=\"error c_error\"><strong>ERROR</strong>: ".$e->getMessage()."</div>";
        }
    }

    public function certificate(){
        try{
            if(!isset($_GET['c_info'])) throw new \Exception("Firma nenelazena.");

            $q = $this->selectrow($this->db->prefix.'certificates', $_GET['c_info']);
            
            if(!$q) throw new \Exception("Firma nenalezena.");
            $r = "";

            if(!empty($q->fotka)) $r = "<img src=\"$q->fotka\" alt=\"Fotka\">";
            $r.= '<table class="table"><tbody>';
            $r.= '<tr><th>Název firmy</th><td>'.esc_html($q->nazev).'</td></tr>';
            $r.= '<tr><th>IČO</th><td>'.esc_html($q->ico).'</td></tr>';
            $r.= '<tr><th>Adresa</th><td>'.esc_html($q->adresa).'</td></tr>';
            $r.= '<tr><th>Typ certifikátu</th><td>'.$q->certifikat.'</td></tr>';
            $r.= '<tr><th>Kontaktní osoba</th><td>'.esc_html($q->kontaktni_osoba).'<br/>Telefon: '.$q->telefon.'<br/>Email: <a href="mailto:'.$q->email.'">'.$q->email.'</a></td></tr>';
			$r.= '<tr><th>Webová stránka: </th><td><a href="https://'.$q->web.'">'.$q->web.'</a></td></tr>';
			$r.= '<tr><th>Obor podnikání: </th><td>'.esc_html($q->obor).'</td></tr>';
            $r.= '<tr><th>Počet nekuřáků: </th><td>'.esc_html($q->pocet_nekuraku).'</td><th>Počet zaměstnanců</th><td>'.$q->pocet_zamestnancu.'</td><th>Poměr nekuřáků / zaměstnanců:</th><td>'.round($q->pocet_nekuraku/$q->pocet_zamestnancu*100, 1).'%</td></tr>';
            $r.= '<tr><th>Přidání certifikátu: </th><td>'.date("d.m.Y",strtotime($q->datum_pridani)).'</td><th>Poslední změna: </th><td>'.date("d.m.Y", strtotime($q->datum_zmeny)).'</td></tr>';
            $r.='</tbody></table>';
            return $r;

        } catch (\Exception $e){
            return "<div class=\"error c_error\"><strong>ERROR</strong>: ".$e->getMessage()."</div>";
        }
    }

}
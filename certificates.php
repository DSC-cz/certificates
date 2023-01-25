<?php
/**
 * Plugin Name: Certifikáty
 * Description: Systém
 * Version: 1.0
 */
    global $wpdb;

    include 'bin/Database.php';
    include 'bin/Admin.php';
    include 'bin/Shortcodes.php';

    $database = new Certificates\Database($wpdb);

    try{
        $database->checkTable($wpdb->prefix.'certificates');

        $admin = new Certificates\Admin($wpdb);
        $shortcodes = new Certificates\Shortcodes($wpdb);
    }
    catch(Exception $e){
        die($e->getMessage());
    }

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        if(isset($_POST['c_send'])){
            if(empty($_POST['c_ico'])){
				echo "<script>alert(\"Nebylo vyplněno IČO!\");</script>";
				return;
			}else if(empty($_POST['c_nazev'])){
				echo "<script>alert(\"Nebyl vyplněn název firmy!\");</script>";
				return;
			} else if(empty($_POST['c_okres'])){
				echo "<script>alert(\"Nebyl vyplněn okres!\");</script>";
				return;
			} else if(empty($_POST['c_obec'])){
				echo "<script>alert(\"Nebyla vyplněna obec!\");</script>";
				return;
			} else if(empty($_POST['c_adresa'])){
				echo "<script>alert(\"Nebyla vyplněna adresa firmy!\");</script>";
				return;
			} else if(empty($_POST['c_web'])){
				echo "<script>alert(\"Nebyl vyplněn web firmy!\");</script>";
				return;
			} else if(empty($_POST['c_obor'])){
				echo "<script>alert(\"Nebyl vyplněn obor podnikání firmy!\");</script>";
				return;
			} else if(empty($_POST['c_telefon'])){
				echo "<script>alert(\"Nebylo vyplněno telefonní číslo\");</script>";
				return;
			} else if(empty($_POST['c_email'])){
				echo "<script>alert(\"Nebyla vyplněna emailová adresa!\");</script>";
				return;
			} else if(empty($_POST['c_kontaktni_osoba'])) {
				echo "<script>alert(\"Nebyla vyplněna kontaktní osoba!\");</script>";
				return;
			}
			else if(empty($_POST['c_pocet_zamestnancu'])) {
				echo "<script>alert(\"Nebyl vyplněn počet zaměstnanců!\");</script>";
				return;
			}
			else if(empty($_POST['c_pocet_nekuraku'])) {
				echo "<script>alert(\"Nebyl vyplněn počet nekuřáků!\");</script>";
				return;
			}
			else if($_POST['c_certifikat'] != "on" && $_POST["c_partner"] != "on") {
				echo "<script>alert(\"Musíte zatrhnout alespoň jedno tlačítko z 'Mám zájem o certifikát' nebo 'Mám zájem stát se partnerem projektu'\");</script>";
				return;
			}
            else{
                try{
					if(!empty($_FILES['c_image']['name']) && !in_array(strtolower(pathinfo($_FILES['c_image']["name"], PATHINFO_EXTENSION)), ["jpg", "png", "jpeg"])) throw new Exception("Soubor není obrázek");

                    $q = $database->insertColumn($wpdb->prefix.'certificates', $_POST['c_ico'], $_POST['c_nazev'], !empty($_FILES['c_image']['name']) ? $_FILES['c_image'] : null, $_POST['c_telefon'], $_POST['c_email'], $_POST['c_kontaktni_osoba'], $_POST['c_okres'], $_POST['c_obec'], $_POST['c_adresa'], $_POST['c_web'], $_POST['c_obor'], ($_POST["c_certifikat"] == "on" ? "Certifikát<br/>" : "").($_POST["c_partner"] == "on" ? "Partner" : ""), $_POST["c_pocet_zamestnancu"], $_POST["c_pocet_nekuraku"]);
                    if($q){
						echo "<script>alert(\"Firma byla odeslána k posouzení.\");</script>";
						if(!empty(get_option('certificates_email'))) mail(get_option('certificates_email'), "nekurackaspolecnost.cz • Nová žádost o certifikaci", "Firma ".$_POST["c_nazev"]." si právě požádala o certifikát.\n\nKliknutím na níže přiložený odkaz se přesunete do administrace, kde jsou podrobnější informace.\n <a href='https://www.nekurackaspolecnost.cz/wp-admin/admin.php?page=certificates-menu-sub-unauthorized'>https://www.nekurackaspolecnost.cz/wp-admin/admin.php?page=certificates-menu-sub-unauthorized</a>\n\nZasláno automaticky z webové stránky ".$_SERVER["SERVER_NAME"], "From: ".get_bloginfo("name")." <no-reply@".str_replace("www.", "", $_SERVER["SERVER_NAME"]).">\n");
					}
                } catch (Exception $e){
                    echo "<script>alert(\"".$e->getMessage()."\");</script>";
                }
            }
        }
    }
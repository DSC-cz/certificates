<?php

    namespace Certificates;

    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-includes/pluggable.php' );

    if(!isset($_GET['c_page'])) $_GET['c_page'] = 1; 

    class Database{
        protected $db;

        public function __construct($db){
            $this->db = $db;
			$this->db->set_charset("utf8mb4");
        }

        public function checkTable($table_name){
            $sql = "CREATE TABLE $table_name (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, `nazev` VARCHAR(255) COLLATE `utf8mb4_general_ci` NOT NULL, `ico` INT(11) NOT NULL, `okres` VARCHAR(255) COLLATE `utf8mb4_general_ci` NULL, `obec` VARCHAR(255) COLLATE `utf8mb4_general_ci` NULL, `adresa` VARCHAR(255) COLLATE `utf8mb4_general_ci` NULL, `fotka` TEXT, `schvalena` BOOLEAN DEFAULT 0, `hlavni_stranka` BOOLEAN DEFAULT 0, `telefon` INT(9) NOT NULL, `email` VARCHAR(255) NOT NULL, `kontaktni_osoba` VARCHAR(255) COLLATE `utf8mb4_general_ci` NOT NULL, `certifikat` VARCHAR(100) DEFAULT 'bronzovy', `web` VARCHAR(255) COLLATE `utf8mb4_general_ci` NULL, `obor` VARCHAR(255) COLLATE `utf8mb4_general_ci` NULL, `check` VARCHAR(255) COLLATE `utf8mb4_general_ci` NULL, `datum_pridani` DATETIME NULL, `datum_zmeny` DATETIME NULL, `pocet_zamestnancu` INT DEFAULT 1, `pocet_nekuraku` INT DEFAULT 1)";
            
            if($this->db->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $q = $this->db->query($this->db->prepare($sql));

                if($q) return true;
                else throw new \Exception("Nepodařilo se vytvořit tabulku '".$table_name."', plugin nelze správně spustit.");
            }

            return true;
        }

        public function insertColumn($table_name, $ico, $nazev, $fotka = null, $telefon, $email, $kontaktni_osoba, $okres = null, $obec = null, $adresa = null, $web = null, $obor = null, $checkbox = null, $pocet_zamestnancu = 1, $pocet_nekuraku = 1){
            $check_sql = $this->db->prepare("SELECT * FROM `$table_name` WHERE `ico`=$ico");
            $check = $this->db->query($check_sql);

            if($check) throw new \Exception("Antispam: Tahle firma je ve schvalovacím procesu !");

			if(!empty($fotka)){
				$file_ext=strtolower(end(explode('.',$fotka['name'])));

				$fotka['name'] = $_POST['c_ico'].'.'.$file_ext;

				if(!wp_handle_upload($fotka, ['test_form'=>false])) throw new \Exception("Nepodařilo se nahrát fotku na server.");
			}

            $sql = $this->db->prepare("INSERT INTO `$table_name` (`ico`, `nazev`, `okres`, `obec`, `adresa`, `fotka`, `telefon`, `email`, `kontaktni_osoba`, `web`, `obor`, `check`, `pocet_zamestnancu`, `pocet_nekuraku`) VALUES('$ico', '$nazev', ".(isset($okres) ? "'$okres', " : "NULL, ").(isset($obec) ? "'$obec', " : "NULL, ").(isset($adresa) ? "'$adresa', " : "NULL, ").(!empty($fotka) ? "'".wp_get_upload_dir()["url"].'/'.$_POST['c_ico'].'.'.$file_ext."'," : 'NULL,')." '$telefon', '$email', '$kontaktni_osoba', '$web', '$obor', '$checkbox', '$pocet_zamestnancu', '$pocet_nekuraku')");
            $q = $this->db->query($sql);

            if($q) return true;
            else throw new \Exception("Nepodařilo se nyní poslat ke schválení firmu.");
        }
		
		public function edit($ico_id, $table_name, $nazev, $ico, $obec, $okres, $adresa, $kontaktni_osoba, $telefon, $email, $web, $obor, $pocet_zamestnancu, $pocet_nekuraku, $fotka){
			$update = $this->db->prepare("UPDATE `$table_name` SET `nazev`='$nazev', `ico`='$ico', `obec`='$obec', `okres`='$okres', `adresa`='$adresa', `kontaktni_osoba`='$kontaktni_osoba', `telefon`='$telefon', `email`='$email', `web`='$web', `obor`='$obor', `pocet_zamestnancu`='$pocet_zamestnancu', `pocet_nekuraku`='$pocet_nekuraku'".(!empty($fotka) ? ", `fotka`='$fotka'" : "")." WHERE `ico`='$ico_id'");
            $q = $this->db->query($update);
			
			if($q) return true;
			else return false;
		}

        public function selectrow($table_name, $ico){
            $sql = $this->db->prepare("SELECT * FROM `$table_name` WHERE `ico`='$ico'");
            $q = $this->db->get_row($sql);

            return $q;
        }

        public function select($table_name, $mainpage = "false", $limit = 9){
            $check_sql = $this->db->prepare("SELECT * FROM `$table_name` ".($mainpage == "false" ? 'WHERE `schvalena`=1' : 'WHERE `schvalena`=1 ORDER BY `id` DESC').' LIMIT '.$limit.' OFFSET '.(($_GET['c_page']-1)*$limit));
            $check = $this->db->get_results($check_sql);

            if($check) return $check;
            else throw new \Exception("Nebyla nalezena žádná data");
        }

        public function getnum($table_name, $mainpage = false){
            $check_sql = $this->db->prepare("SELECT COUNT(*) as `count` FROM `$table_name` WHERE `schvalena`=1");
            $check = $this->db->get_results($check_sql);

            return $check[0]->count;
        }

        public function authorize($table_name, $ico){
            $sql = $this->db->prepare("UPDATE `$table_name` SET `schvalena`='1', `datum_pridani`=now(), `datum_zmeny`=now() WHERE `ico`='$ico'");
            $q = $this->db->query($sql);

            if($q) return true;
            else return false;
        }

        public function unauthorize($table_name, $ico){
            $sql = $this->db->prepare("DELETE FROM `$table_name` WHERE `ico`=$ico");
            $q = $this->db->query($sql);

            if($q) return true;
            else return false;
        }

        public function mainpage($table_name, $ico){
            $sql = $this->db->prepare("UPDATE `$table_name` SET `hlavni_stranka`=1 WHERE `ico`=$ico");
            $q = $this->db->query($sql);

            if($q) return true;
            else return false;
        }

        public function unmainpage($table_name, $ico){
            $sql = $this->db->prepare("UPDATE `$table_name` SET `hlavni_stranka`=0 WHERE `ico`=$ico");
            $q = $this->db->query($sql);

            if($q) return true;
            else return false;
        }

        public function certifikat($table_name, $ico, $typ = 'bronzovy'){
            $sql = $this->db->prepare("UPDATE `$table_name` SET `certifikat`='$typ',`datum_zmeny`=now() WHERE `ico`=$ico");
            $q = $this->db->query($sql);

            if($q) return true;
            else return false;
        }

    }


?>
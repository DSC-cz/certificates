<?php

namespace Certificates;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if(empty($_GET['offset'])) $_GET['offset'] = 0;


class TableList extends \WP_List_Table{

    public function get_columns() {
        return array(
            'nazev'   => wp_strip_all_tags( __( 'Název' ) ),
            'ico'      => wp_strip_all_tags( __( 'IČO' ) ),
            'fotka'   => wp_strip_all_tags( __('Fotka') ),
            'obec'   => wp_strip_all_tags( __( 'Obec' ) ),
            'okres'   => wp_strip_all_tags( __( 'Okres' ) ),
            'adresa'   => wp_strip_all_tags( __( 'Adresa' ) ),
            'kontakt'   => wp_strip_all_tags( __( 'Kontakt' ) ),
			'web'   => wp_strip_all_tags( __( 'Web' ) ),
			'obor'   => wp_strip_all_tags( __( 'Obor' ) ),
            'statistika'   => wp_strip_all_tags( __( 'Statistika' ) ),
			'check'   => wp_strip_all_tags( __( ' ' ) ),
            'schvalena'   => wp_strip_all_tags( __( 'Schválení' ) ),
        );
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = array();
        $primary  = 'nazev';

        $this->_column_headers = array( $columns, $hidden, $sortable, $primary );

    }

    protected function column_default( $item, $column_name ) {
		echo "<form action=\"\" method=\"POST\">";
        switch ( $column_name ) {
            case 'fotka':
                 return (!empty($item['fotka']) ? '<img src="'.$item['fotka'].'" alt="fotka" width="50px" />' : '');
            case 'nazev':
                return esc_html( $item['nazev'] );
            case 'ico':
                return esc_html( $item['ico'] );
            case 'okres':
                return esc_html( $item['okres'] );
            case 'obec':
                return esc_html( $item['obec'] );
            case 'adresa':
                return esc_html( $item['adresa'] );
            case 'kontakt':
                return $item['kontakt'];
			case 'web':
				return "<a href=\"https://".$item["web"]."\">".$item["web"].'</a>';
			case 'obor':
				return esc_html( $item["obor"] );
            case 'statistika':
                return $item["statistika"];
			case 'check':
				return $item["check"];
            case 'schvalena':
                return $item['schvalena'].'</form><a href="?page=certificate-edit&ico='.$item["ico"].'"><button>Upravit</button></a>';
            return 'Unknown';
        }
    }

    protected function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions( $which ); ?>
            </div>
            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            
            ?>

            <br class="clear" />
        </div>
        <?php
    }

    public function single_row( $item ) {
        echo '<tr>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }
}

class Admin{
    protected $db;

    public function __construct($wpdb){
        $this->db = $wpdb;

        add_action('admin_menu', array($this, 'certificates_menu'));
		add_action('admin_init', array($this, 'certificates_register_settings'));
		add_action('admin_menu', array($this, 'certificates_settings_menu'));
    }
	
	public function certificates_register_settings(){
		register_setting('certificates_settings', 'certificates_email');
	}
	
	public function certificates_settings_menu(){
		add_options_page('Certifikáty', 'Certifikáty - Nastavení', 'manage_options', 'certificates-setting', array($this, 'certificates_settings_page'));
	}
	
	public function certificates_settings_page(){
		?>
			<h1>Certifikáty - nastavení</h1>
			<form method="post" action="options.php">
            <?php settings_fields('certificates_settings'); ?>
			<input type = 'email' placeholder="Odesílat notifikace na email" class="regular-text" id="certificates_email_id" name="certificates_email" value="<?php echo get_option('certificates_email'); ?>">
			<?php submit_button(); ?>
		</form>
		<?php
	}

    public function certificates_menu(){
        add_menu_page('Certifikáty', 'Certifikáty', 'manage_options', 'certificates-menu', array($this, 'certificates_main'), 'dashicons-admin-site-alt', 2);
        add_submenu_page('certificates-menu', 'Neschválené certifikáty', 'Neschválené', 'manage_options', 'certificates-menu-sub-unauthorized', array($this, 'certificates_unauthorized'));
		add_submenu_page('certificate-edit', 'Úprava certifikátu', null, 'manage_options', 'certificate-edit', array($this, 'certificate_edit'));
    }

    public function certificates_main(){
        echo '<h2>Všechny certifikáty</h2>';
		
		?>
			<form class="float-right text-right" method="get">
				<input type="hidden" name="page" value="certificates-menu" />
				<input type="text" name="find" value="<?=isset($_GET['find']) ? $_GET['find'] : ""?>" placeholder="Hledat podle IČO" />
				<input type="submit" value="Hledat">
			</form>
		<?php

        $rows = $this->db->get_row('SELECT COUNT(*) as `count` FROM '.$this->db->prefix.'certificates'.(isset($_GET['find']) ? " WHERE `ico` = ".$_GET['find'] : ""));
        $q = $this->db->get_results('SELECT * FROM `'.$this->db->prefix.'certificates` '.(isset($_GET['find']) ? " WHERE `ico` = ".$_GET['find'] : "").' LIMIT 10 OFFSET '.$_GET['offset']);

        if($q){
            $table = new TableList(count($q));
            foreach($q as $item){
                $table->items[] = [
                    'ico'=>$item->ico,
                    'fotka'=>$item->fotka,
                    'nazev'=>$item->nazev,
                    'obec'=>$item->obec,
                    'okres'=>$item->okres,
                    'adresa'=>$item->adresa,
                    'kontakt'=>$item->kontaktni_osoba.'<br/>'.$item->telefon.'<br/>'.$item->email,
					'web'=>$item->web,
					'obor'=>$item->obor,
					'check'=>$item->check,
                    'statistika'=>'Zaměstnanců'.$item->pocet_zamestnancu.'<br/>Nekuřáků:'.$item->pocet_nekuraku,
                    'schvalena'=>($item->schvalena == 0 ? "<button value=\"$item->ico\" name=\"authorize\">Schválit</button>" : "<button value=\"$item->ico\" name=\"unauthorize\">Odebrat</button>".($item->hlavni_stranka == 1 ? ""/*"<button value=\"$item->ico\" name=\"unmainpage\">Odebrat z úvodní stránky</button>"*/ : ""/*"<button value=\"$item->ico\" name=\"mainpage\">Přidat na úvodní stránku</button>"*/)."<button name=\"golden\" value=\"$item->ico\" ".($item->certifikat == "zlaty" ? "disabled=\"disabled\"" : "").">Zlatý</button>"."<button name=\"silver\" value=\"$item->ico\" ".($item->certifikat == "stribrny" ? "disabled=\"disabled\"" : "").">Stříbrný</button>"."<button name=\"bronzed\" value=\"$item->ico\" ".($item->certifikat == "bronzovy" ? "disabled=\"disabled\"" : "").">Bronzový</button>")
                ];
            }

            $table->prepare_items();
            $table->display();

            if($_GET['offset']-10 >= 0) echo '<a href="?page=certificates-menu&offset='.($_GET['offset']-10).'">Předchozí</a>';
            if($rows->count > $_GET['offset']+10) echo '<a href="?page=certificates-menu&offset='.($_GET['offset']+10).'">Další</a>';
        }
        else echo "Žádné certifikáty"; 
    }

    public function certificates_unauthorized(){
        echo '<h2>Neschválené certifikáty</h2>';

        $rows = $this->db->get_row('SELECT COUNT(*) as `count` FROM '.$this->db->prefix.'certificates WHERE `schvalena` = 0');
        $q = $this->db->get_results('SELECT * FROM '.$this->db->prefix.'certificates WHERE `schvalena` = 0 LIMIT 10 OFFSET '.$_GET['offset']);

        if($q){
            $table = new TableList();
            foreach($q as $item){
                $table->items[] = [
                    'ico'=>$item->ico,
                    'fotka'=>$item->fotka,
                    'nazev'=>$item->nazev,
                    'obec'=>$item->obec,
                    'okres'=>$item->okres,
                    'adresa'=>$item->adresa,
                    'kontakt'=>$item->kontaktni_osoba.', '.$item->telefon.', '.$item->email,
					'web'=>$item->web,
					'obor'=>$item->obor,
					'check'=>$item->check,
                    'statistika'=>$item->statistika,
                    'schvalena'=>($item->schvalena == 0 ? "<button value=\"$item->ico\" name=\"authorize\">Schválit</button>" : "Schválená")
                ];
            }

            $table->prepare_items();
            $table->display();

            if($_GET['offset']-10 >= 0) echo '<a href="?page=certificates-menu&offset='.($_GET['offset']-10).'">Předchozí</a> ';
            if($rows->count > $_GET['offset']+10) echo '<a href="?page=certificates-menu&offset='.($_GET['offset']+10).'">Další</a>';

        }
        else echo "Žádné neschválené certifikáty"; 
    }
	
	
	public function certificate_edit(){
		global $wpdb;
		$database = new Database($wpdb);
		
		if(!isset($_GET['ico'])) die("Musíte zadat IČO");
		
		$item = $database->selectrow($wpdb->prefix.'certificates', $_GET['ico']);
		
		?>
			<h1>
				Úprava certifikátu firmy <?=$item->nazev?>
</h1>
			<form action="" method="POST" enctype='multipart/form-data'>
				<input type="text" name="nazev" value="<?=$item->nazev?>" placeholder="Název firmy" />
				<input type="text" name="ico" value="<?=$item->ico?>" placeholder="IČO firmy" />
				<input type="text" name="okres" value="<?=$item->okres?>" placeholder="Okres" />
				<input type="text" name="obec" value="<?=$item->obec?>" placeholder="Obec" />
				<input type="text" name="adresa" value="<?=$item->adresa?>" placeholder="Adresa" />
				<input type="text" name="web" value="<?=$item->web?>" placeholder="Web" />
				<input type="text" name="obor" value="<?=$item->obor?>" placeholder="Obor podnikání" />
                <input type="number" name="pocet_zamestnancu" value="<?=$item->pocet_zamestnancu?>" placeholder="Počet zaměstnanců" />
                <input type="number" name="pocet_nekuraku" value="<?=$item->pocet_nekuraku?>" placeholder="Počet nekuřáků" />
				<label for="fotka">Nový obrázek firmy</label>
				<input type="file" name="fotka" id="fotka" accept="image/*"/>
				<input type="text" name="kontaktni_osoba" value="<?=$item->kontaktni_osoba?>" placeholder="Kontaktní osoba" />
				<input type="number" name="telefon" value="<?=$item->telefon?>" placeholder="Telefon" />
				<input type="email" name="email" value="<?=$item->email?>" placeholder="Email" />
				<button value="<?=$item->ico?>" name="edit">
					Upravit
				</button>
				
</form>
<a href="javascript:history.back()"><button>
	Zpět
	</button></a>
<style>input,button{width:90%;display:block;margin-top:3px;}</style>
		<?php
	}

}

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $database = new Database($wpdb);

    if(isset($_POST['authorize'])){
        $q = $database->authorize($wpdb->prefix.'certificates', $_POST['authorize']);
        if($q) echo "<script>alert(\"Firma byla schválena.\");</script>";
        else echo "<script>alert(\"Nastala chyba, nelze nyní schválit firmu.\");</script>";
    }

    if(isset($_POST['unauthorize'])){
        try{
            $get_data = $database->selectrow($wpdb->prefix.'certificates', $_POST['unauthorize']);
			
            if(file_exists("..".str_replace(get_bloginfo("url"), "", $get_data->fotka))){
				!unlink("..".str_replace(get_bloginfo("url"), "", $get_data->fotka));
			}
            $q = $database->unauthorize($wpdb->prefix.'certificates', $_POST['unauthorize']);
            if($q) echo "<script>alert(\"Firma byla odebrána.\");</script>";
            else throw new \Exception("Nastala chyba, nelze nyní odebrat firmu.");
        } catch (\Exception $e){
            echo  "<script>alert(\"".$e->getMessage()."\");</script>";
        }
    }

    if(isset($_POST['mainpage'])){
        $q = $database->mainpage($wpdb->prefix.'certificates', $_POST['mainpage']);
        if($q) echo "<script>alert(\"Firma byla nastavena na hlavní stránku\");</script>";
        else echo "<script>alert(\"Nastala chyba, nelze nyní přidat firmu na hlavní stránku\");</script>";
    }

    if(isset($_POST['unmainpage'])){
        $q = $database->unmainpage($wpdb->prefix.'certificates', $_POST['unmainpage']);
        if($q) echo "<script>alert(\"Firma byla zrušena z hlavní stránky\");</script>";
        else echo "<script>alert(\"Nastala chyba, nelze nyní odebrat firmu z hlavní stránky\");</script>";
    }

    if(isset($_POST['golden'])){
        $q = $database->certifikat($wpdb->prefix.'certificates', $_POST['golden'], 'zlaty');
        if($q) echo "<script>alert(\"Firmě byl přidělen zlatý certifikát\");</script>";
        else echo "<script>alert(\"Nastala chyba, nelze nyní udělit firmě certifikát\");</script>";
    }

    if(isset($_POST['silver'])){
        $q = $database->certifikat($wpdb->prefix.'certificates', $_POST['silver'], 'stribrny');
        if($q) echo "<script>alert(\"Firmě byl přidělen stříbrný certifikát\");</script>";
        else echo "<script>alert(\"Nastala chyba, nelze nyní udělit firmě certifikát\");</script>";
    }

    if(isset($_POST['bronzed'])){
        $q = $database->certifikat($wpdb->prefix.'certificates', $_POST['bronzed'], 'bronzovy');
        if($q) echo "<script>alert(\"Firmě byl přidělen bronzový certifikát\");</script>";
        else echo "<script>alert(\"Nastala chyba, nelze nyní udělit firmě certifikát\");</script>";
    }
	
	if(isset($_POST['edit'])){
		try{
            if(!empty($_FILES["fotka"]["name"])){
				$file_ext=strtolower(end(explode('.',$_FILES['fotka']['name'])));

				$_FILES["fotka"]["name"] = $_POST['ico'].'.'.$file_ext;

                $get_data = $database->selectrow($wpdb->prefix.'certificates', $_POST["edit"]);
                if(file_exists("..".str_replace(get_bloginfo("url"), "", $get_data->fotka))) !unlink("..".str_replace(get_bloginfo("url"), "", $get_data->fotka));

				if(!wp_handle_upload($_FILES["fotka"], ['test_form'=>false])) throw new \Exception("Nepodařilo se nahrát fotku na server.");
			}

			$q = $database->edit($_POST["edit"], $wpdb->prefix.'certificates', $_POST["nazev"], $_POST["ico"], $_POST["obec"], $_POST["okres"], $_POST["adresa"], $_POST["kontaktni_osoba"], $_POST["telefon"], $_POST["email"], $_POST['web'], $_POST['obor'], $_POST["pocet_zamestnancu"], $_POST["pocet_nekuraku"], !empty($_FILES["fotka"]["name"]) ? wp_get_upload_dir()["url"].'/'.$_POST['ico'].'.'.$file_ext : "");

			if($q) echo "<script>alert(\"Firma upravena\")</script>";
			else echo "<script>alert(\"Nastala chyba, nelze nyní upravit firmu.\")</script>";
		} catch (\Exception $e){
			echo "<script>alert(\"".$e->getMessage()."\")</script>";
		}
	}
}
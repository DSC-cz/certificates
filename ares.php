<?php
    if(!isset($_GET['ico'])) die();

    $get = file_get_contents("https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_std.cgi?ico=".$_GET['ico']);

    $xml = simplexml_load_string( $get );

    $ns = $xml->getDocNamespaces();
    $data = $xml->children($ns['are']);

    if(!isset($data->Odpoved->Zaznam->Identifikace->Adresa_ARES)) die(json_encode(["error"=>"Firma nenalezena v ARES rejstříku."]));

    $adresa_ares = $data->Odpoved->Zaznam->Identifikace->Adresa_ARES->children($ns['dtt']);
    
    $info = [
        'Obchodni_firma'=> $data->Odpoved->Zaznam->Obchodni_firma,
        'ICO'=>  $data->Odpoved->Zaznam->ICO,
        'Okres'=> $adresa_ares->Nazev_okresu,
        'Obec'=> $adresa_ares->Nazev_casti_obce.', '.$adresa_ares->Nazev_obce,
        'Adresa'=>$adresa_ares->Nazev_ulice.' '.$adresa_ares->Cislo_domovni.'/'.$adresa_ares->Typ_cislo_domovni.', '.$adresa_ares->PSC.' '.$adresa_ares->Nazev_mestske_casti,
        'Datum_vzniku'=>$data->Odpoved->Zaznam->Datum_vzniku,
        'Datum_platnosti'=>$data->Odpoved->Zaznam->Datum_platnosti,
    ];

    echo json_encode($info);

?>
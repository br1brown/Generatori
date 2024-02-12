<?php
require_once __DIR__.'/parsedown-1.7.4/Parsedown.php';

$parser = null;
function Markdown_HTML($mark){

if (!isset($parser))
    $parser = new Parsedown();

return $parser->text($mark);

}



/**
 * Modifica l'array del menu direttamente per riferimento.
 *
 * @param array &$itemsMenu Riferimento all'array del menu ottenuto dal JSON statico.
 */
function dynamicMenu($generatori, &$itemsMenu) {

    foreach ($generatori as $gen): 
        $itemsMenu[] = [
            'nome' => htmlspecialchars(str_replace("Generatore", "",$gen['nome'])),
            'route' => htmlspecialchars($gen['url'])
        ];
    endforeach;
    // Qui puoi modificare direttamente l'array $itemsMenu.

    // Esempio: Aggiungere un nuovo elemento al menu
    // $itemsMenu[] = [
    //     'nome' => 'Nuova Voce',
    //     'route' => 'nuova-voce-route'
    // ];

    // Esempio: Modificare un elemento esistente
    // foreach ($itemsMenu as $key => &$item) {
    //     if ($item['nome'] == 'ElementoDaModificare') {
    //         $item['route'] = 'nuovo-route-modificato';
    //     }
    // }
    // Nota: Non dimenticare di rimuovere il riferimento dopo il ciclo
    // unset($item);

    // Esempio: Rimuovere un elemento
    // $itemsMenu = array_filter($itemsMenu, function($item) {
    //     return $item['nome'] != 'ElementoDaRimuovere';
    // });

    // Non è necessario restituire l'array, poiché è stato passato per riferimento
}


function actuaconfigGen(){
    $data = [];
    $data['directory'] = 'infogeneratori/';
    // Chiave GET per identificare il nome del generatore.
    $data['chiaveGET'] = 'gen';

    // Controlla se il nome del generatore è stato fornito tramite GET.
    if (isset($_GET[$data['chiaveGET']]) && !empty($_GET[$data['chiaveGET']]))
        $data['nomeGeneratore'] =  strtolower($_GET[$data['chiaveGET']]);

    return $data;
}

function caricaGeneratori($Service, $forzaTutti = false) {

    $config = actuaconfigGen();
    // Normalizza il nome del generatore se fornito.
    $nomeGeneratore = $forzaTutti?null:$config['nomeGeneratore']??null;
    $chiaveGET = $config['chiaveGET'];
    $directory = $config['directory'];


    // Array per memorizzare i dati dei generatori.
    $generatori = []; 

    // Costruisce la base dell'URL con il parametro della query.
    $baseUrl = '?'.$chiaveGET.'=';

    // Itera su tutti i file nella directory specificata.
    foreach (new DirectoryIterator($directory) as $file) {
        // Verifica se il file corrente è un file e ha estensione 'json'.
        if ($file->isFile() && $file->getExtension() === 'json') {
            // Ottiene il nome del file senza estensione.
            $nomeFileSenzaEstensione = strtolower(pathinfo($file->getFilename(), PATHINFO_FILENAME));

            // Se è stato specificato un nome generatore, confronta con il nome del file corrente.
            if (empty($nomeGeneratore) || $nomeFileSenzaEstensione === $nomeGeneratore) {
                // Legge il contenuto del file e decodifica il JSON.
                $jsonContent = file_get_contents($directory . $file->getFilename());
                $data = json_decode($jsonContent, true);
                // Aggiunge una chiave 'selfchiaveGET' con il nome del file.
                $data['selfchiaveGET'] = $nomeFileSenzaEstensione;
                // Aggiunge l'URL per il generatore.
                $data['url'] = $Service->baseURL($baseUrl . urlencode($nomeFileSenzaEstensione));
                // Aggiunge i dati all'array dei generatori.
                $generatori[] = $data;
            }
        }
    }

    // Ordina l'array in base al campo 'posizione'.
    usort($generatori, function($a, $b) {
        return $a['posizione'] - $b['posizione'];
    });

    // Restituisce solo l'array dei generatori.
    return $generatori;
}


?>

<?php
/**
 * Ottiene un oggetto e lo restituisce, eventualmente dopo aver applicato una callback.
 * 
 * @param string $nome Nome dell'oggetto da ottenere.
 * @param callable|null $callback Funzione di callback da applicare ai dati.
 * @return string Risposta JSON con i dati ottenuti o un messaggio di errore.
 */
function Echo_getObj($nome, $callback = null)
{
    // Controlla se la callback fornita è eseguibile
    $ciLavoro = is_callable($callback);

    try {
        // Richiede i dati all'oggetto Repository
        $jsonData = BLL\Repository::getObj($nome, $ciLavoro);

        // Se esiste una callback valida, la applica ai dati ottenuti
        if ($ciLavoro) {
            $l = isset($_GET["lang"]) ? filter_input(INPUT_GET, "lang", FILTER_SANITIZE_STRING) : BLL\Repository::getDefaultLang();
            $jsonData = json_encode($callback($jsonData, $l));
        }

    } catch (Exception $e) {
        // In caso di eccezione, restituisce un messaggio di errore
        return BLL\Response::retError($e->getMessage());
    }

    // Restituisce i dati in formato JSON
    return $jsonData;
}

function getResult($resultText, $resultMarkdown = null, $resultHtml = null) {
    $response = ['text' => $resultText];

    if (isset($_GET['markdown']) && $_GET['markdown'] == 'true') {
        $response['markdown'] = $resultMarkdown !== null ? $resultMarkdown : $resultText;
    }

    if (isset($_GET['html']) && $_GET['html'] == 'true') {
        $response['html'] = $resultHtml !== null ? $resultHtml : $resultText;
    }

    return $response;
}

function FormatMarkdownString($originalString){
    $ReturnString = $originalString;
    // Rende il testo tra parentesi corsivo
    $ReturnString = preg_replace('/\((.*?)\)/', '*($1)*', $ReturnString);
    return $ReturnString;
}

function selezionaCasualeSenzaDoppioni($array, $chiave, &$result) {
    $selezionati = []; // Array per tenere traccia degli elementi già usati

    while(strpos($result, '['.$chiave.']') !== false) {
        // Filtra l'array per rimuovere gli elementi già selezionati
        $possibiliScelte = array_diff($array, $selezionati);

        // Se non ci sono più elementi da scegliere, resetta l'array dei selezionati
        if (empty($possibiliScelte)) {
            $selezionati = [];
            $possibiliScelte = $array;
        }

        // Seleziona casualmente un elemento
        $scelta = $possibiliScelte[array_rand($possibiliScelte)];
        $selezionati[] = $scelta; // Aggiunge l'elemento selezionato alla lista dei selezionati

        // Sostituisce l'elemento nel risultato
        $result = substr_replace($result, $scelta, strpos($result, '['.$chiave.']'), strlen('['.$chiave.']'));
    }
}

function ranges_eta($returnonlykey = false) {
    $range = [
        "[ia]" => "[0-6]",   // infant age
        "[ba]" => "[6-13]",  // baby age
        "[ta]" => "[13-19]", // teen age
        "[sa]" => "[19-27]", // small age
        "[ma]" => "[27-60]", // middle age
        "[oa]" => "[50-76]"  // old age
    ];

    if ($returnonlykey) {
        return array_keys($range);
    } else {
        return $range;
    }
}

function MaiuscoleDopoPunteggiatura($frase){
    return ucfirst(trim(preg_replace_callback('/([.!?]|\n)\s*\K[\wàèéìòóùÀÈÉÌÒÓÙ]/u', 
    function($m) {
        return mb_strtoupper($m[0]);
    },
    $frase)));
}

function implodeRandomSeparator($separators,$frasi){
    $lastSeparatorIndex = -1;
    $result="";
    for ($i = 0; $i < count($frasi); $i++) {
        $result .= $frasi[$i];
        if ($i < count($frasi) - 1) {
            do {
                $currentSeparatorIndex = array_rand($separators);
            } while ($currentSeparatorIndex == $lastSeparatorIndex);

            $result .= $separators[$currentSeparatorIndex];
            $lastSeparatorIndex = $currentSeparatorIndex;
        }
    }
    return $result;
}

function replaceStandard($jsonData, $frase){
    for ($i = 0; $i < 2; $i++) {
        foreach ($jsonData as $key => $value) {
            if ($key != "core"){
                // Filtra l'array, mantenendo solo gli elementi che sono array
                $subArrays = array_filter($value, 'is_array');

                // Se non ci sono array nell'array (cioè è un array di elementi semplici)
                if (empty($subArrays)) {
                    selezionaCasualeSenzaDoppioni($jsonData[$key], $key, $frase);
                }
            }
        }
    }

    foreach (['social', 'city'] as $value) {
        selezionaCasualeSenzaDoppioni(BLL\Repository::getObj($value), $value, $frase);
    }

    $tutti = getObjNomi();
    selezionaCasualeSenzaDoppioni(array_merge($tutti['maschi'],$tutti['femmine']), "nome", $frase);
    selezionaCasualeSenzaDoppioni($tutti['femmine'], "nome-f", $frase);
    selezionaCasualeSenzaDoppioni($tutti['maschi'], "nome-m", $frase);
    selezionaCasualeSenzaDoppioni($tutti['cognomi'], "cognome", $frase);

    $rangeeta = ranges_eta();
    // Sostituzione delle etichette di età con valori
    foreach(array_keys($rangeeta) as $tipo) {
        while(strpos($frase, $tipo) !== false) {
            $frase = substr_replace($frase, $rangeeta[$tipo], strpos($frase, $tipo), strlen($tipo));
        }
    }
    
    $frase = preg_replace_callback('/\[(\d+)-(\d+)\]/', function ($matches) {
        // $matches[1] è il primo numero e $matches[2] è il secondo numero
        return rand((int)$matches[1], (int)$matches[2]);
    }, $frase);

    return $frase;
}

function selezionaCore($data, $numFrasi, $etichetteUniche = [], $gruppiEtichetteEsclusive = []) {
    $listona = $data['core'];
    $frasiSelezionate = [];
    $etichetteIncontrate = [];
    $etichetteEsclusiveIncontrate = [];
    // Inizializza il dizionario per le etichette esclusive.
    foreach ($gruppiEtichetteEsclusive as $key => $group) {
        $etichetteEsclusiveIncontrate[$key] = false;
    }

    // Continua finché non hai selezionato il numero richiesto di frasi o finché non ci sono più frasi da cui scegliere.
    while (count($frasiSelezionate) < $numFrasi && count($listona) > 0) {
        // Seleziona una frase casuale e rimuovila dalla lista originale.
        $indiceCasuale = array_rand($listona);
        $fraseCasuale = $listona[$indiceCasuale];
        unset($listona[$indiceCasuale]); // Rimuove la frase per evitare duplicati.

        // Flag per determinare se aggiungere la frase all'elenco.
        $aggiungiFrase = true;
        // Controlla le etichette uniche nella frase.
        foreach ($etichetteUniche as $etichetta) {
            if (contieneEtichette($fraseCasuale, [$etichetta])) {
                if (isset($etichetteIncontrate[$etichetta])) {
                    // Se l'etichetta unica è già stata incontrata, non aggiungere la frase.
                    $aggiungiFrase = false;
                    break;
                }
                // Segna l'etichetta unica come incontrata.
                $etichetteIncontrate[$etichetta] = true;
            }
        }

        if ($aggiungiFrase) {
        // Verifica se la frase contiene etichette da gruppi esclusivi già incontrati.
        foreach ($gruppiEtichetteEsclusive as $key => $group) {
            if (contieneEtichette($fraseCasuale, $group)) {
                if ($etichetteEsclusiveIncontrate[$key]===true) {
                    // Se l'etichetta esclusiva è già stata incontrata, non aggiungere la frase.
                    $aggiungiFrase = false;
                    break;
                }
                // Segna l'etichetta esclusiva come incontrata.
                $etichetteEsclusiveIncontrate[$key] = true;
            }
        }
        }

        // Aggiungi la frase all'elenco se supera tutti i controlli.
        if ($aggiungiFrase) {
            $frasiSelezionate[] = $fraseCasuale;
        }
    }

    return $frasiSelezionate;

}

function contieneEtichette($frase, $etichette) {
    foreach($etichette as $etichetta) {
        if (strpos($frase, $etichetta) !== false) {
            return true;
        }
    }
    return false;
}

function randomName($tipo = null, $cognome = false){
    $nomi = [];
    switch ($tipo){
        case "f":
            $nomi = getNomiFemmine();
            break;
        case "m":
            $nomi = getNomiMaschi();
            break;
        default:
            $nomi = getArrayNomi();
            break;
    }
if ($cognome){
    $cognomi = getCognomi();
    return $nomi[array_rand($nomi)].' '.$cognomi[array_rand($cognomi)];
}
else
    return $nomi[array_rand($nomi)];
}


function getObjNomi(){ return BLL\Repository::getObj('nomi');}

function getNomiMaschi(){
    return getObjNomi()['maschi'];
}

function getCognomi(){
    return getObjNomi()['cognomi'];
}

function getNomiFemmine(){
    return getObjNomi()['femmine'];
}
function getArrayNomi(){
    $tutti = getObjNomi();
    return array_merge($tutti['maschi'],$tutti['femmine']);
}
?>

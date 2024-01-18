<?php
require_once __DIR__.'/funzioni.php';
require_once __DIR__.'/parsedown-1.7.4/Parsedown.php';

class Service {

    /**
     * @var Parsedown il parser
     */
    private Parsedown $Parser;
    /**
     * Il parser
     *
     * @return Parsedown Il parser.
     */
    public function getparser() : Parsedown {
        if (!isset($this->Parser))
            $this->Parser =   (new Parsedown());
        return $this->Parser;
    }

    /**
     * @var array Impostazioni dell'applicativo
     */
    private array $settings;

    /**
     * @var array Chiavi da escludere dalle impostazioni quando richiesto.
     */
    private $excludeKeys = ['API',"meta"];
    
    /**
     * Restituisce le impostazioni dell'applicativo necessarie
     *
     * @return array Impostazioni filtrate.
     */
    public function getSettings() {

        $data = array_filter($this->settings, function($key) {
            return !in_array($key, $this->excludeKeys);
        }, ARRAY_FILTER_USE_KEY);

        if (!isset($data['colorTema']) || empty($data['colorTema'])) {
            $data['colorTema'] = "#606060"; 
        }        
        if (!isset($data['colorBase']) || empty($data['colorBase'])) {
           $data['colorBase'] = $this->lightenColor($data['colorTema']);
        }
        $data['isDarkTextPreferred'] = $this->isDarkTextPreferred($data['colorTema']);
                
        $havesmoke = isset($data['smoke']) && $data['smoke']["enable"];
        $data['havesmoke'] = $havesmoke;

        return $data; 
    }

    /**
     * Restituisce le impostazioni dei metatag e header
     *
     * @return array Impostazioni filtrate.
     */
    public function getMeta($title, $description = null) {

        $meta = $this->settings['meta'];

        $meta['title'] = $title?? $this->settings['AppName'];
        $meta['description'] = $description?? $this->settings['description'];

        $szkeywords = "";
        foreach ($this->settings['meta']["keywords"] as $keyword) {
            $szkeywords  .= trim($keyword) . ",";
        }

        $meta['string_All_keywords'] = rtrim($szkeywords, ",");

        $havesmoke = isset($this->settings['smoke']) && $this->settings['smoke']["enable"];

        $firstLoadCss = ["base.css"]; 
        $lastLoadCss = ["addon.css"]; 
        $additionalCss = $this->getFileList("style", "css",  array_merge($firstLoadCss, $lastLoadCss));
        $meta['ordercss'] = array_merge($firstLoadCss, $additionalCss, $lastLoadCss);

        $firstLoadjs = ["base.js"]; 
        $lastLoadjs = ["addon.js"]; 
        $excludeJs = $havesmoke ? [] : ["jquery_bloodforge_smoke_effect.js"];
        $additionaljs = $this->getFileList("script", "js", array_merge($firstLoadjs, $excludeJs, $lastLoadjs) );
        $meta['orderjs'] = array_merge($firstLoadjs, $additionaljs, $lastLoadjs);

        return $meta; 
    }



    /**
     * @var string URL dell'API di servizio
     */
    public string $urlAPI;

    /**
     * @var string Chiave dell'API di servizio
     */
    public string $APIkey;

    /**
     * @var string URL dell'Host
     */
    public string $baseUrl;

    /**
     * Costruttore della classe Service.
     * Legge le impostazioni dal file JSON e inizializza l'URL dell'API.
     */
    public function __construct()
    {
        // Controllo prima la variabile $_SERVER['HTTPS']
        $protocol = 'http'; // Impostazione predefinita a 'http'
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https'; // Se 'HTTPS' è attivo, usa 'https'
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https'; // Se 'HTTP_X_FORWARDED_PROTO' è 'https', usa 'https'
        }

        // Costruzione dell'URL
        $this->baseUrl = $protocol . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/";
                            
        $this->settings = json_decode(file_get_contents('websettings.json'), true);

        $this->APIkey  = $this->settings['API']['key'];

        $APIEndPoint = $this->settings['API']['EndPoint'];
        if (strpos($APIEndPoint, "http://") === 0 || strpos($APIEndPoint, "https://") === 0) {
            $this->urlAPI = $APIEndPoint;
        } else {
            $this->urlAPI = $this->baseUrl.$APIEndPoint;
        }

        dynamicMenu($this, $this->settings['itemsMenu']);
    }

    /**
     * Ottiene un elenco di percorsi relativi di file con una specifica estensione in una data directory,
     * escludendo i file specificati.
     *
     * @param string $directory Il percorso della directory da esplorare.
     * @param string $extension L'estensione dei file da includere nell'elenco.
     * @param array $excludeFiles Array contenente i nomi dei file da escludere.
     * @return array Array di stringhe, ognuna rappresentante il percorso relativo di un file.
     */
    function getFileList($directory, $extension, $excludeFiles = array()) {
        $fileList = array();
        $absolutePath = realpath($directory) . '/';
        foreach (glob($absolutePath . "*." . $extension) as $file) {
            $relativePath = str_replace($absolutePath, '', $file);
            // Assicurarsi di escludere sia i percorsi completi che i soli nomi di file
            $fileName = basename($relativePath);
            if (!in_array($fileName, $excludeFiles) && !in_array($relativePath, $excludeFiles)) {
                $fileList[] = $relativePath;
            }
        }
        return $fileList;
    }


    /**
     * Restituisce il percorso completo dell'URL per una risorsa nelle API.
     * 
     * @param string $path Percorso della risorsa.
     * @return string URL completo della risorsa.
     */
    public function APIbaseURL($path) {
        if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) {
            return $path;
        } else {
            return rtrim($this->urlAPI, '/') . '/' . $path;
        }
    }

    /**
     * Restituisce il percorso completo dell'URL per una risorsa.
     * 
     * @param string $path Percorso della risorsa.
     * @return string URL completo della risorsa.
     */
    public function baseURL($path) {
        if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) {
            return $path;
        } else { 
            return rtrim($this->baseUrl, '/') . '/' . $path;
        }
    }

    /**
     * @var bool controllo gli SSL dell' endpoint?
     */
    public bool $CheckSSL = true;

    /**
     * Esegue una chiamata all'endpoint dell'API utilizzando il metodo HTTP specificato e restituisce la risposta.
     * 
     * @param string $pathOrEndpoint Il percorso dell'endpoint o interno dell'API.
     * @param string $metodo Il metodo HTTP da utilizzare per la chiamata (ad es. 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'). Di default è 'GET'.
     * @param array $dati I dati da inviare con la richiesta, utili per i metodi come 'POST', 'PUT'.
     * @param string $contentType Il Content Type della richiesta, di default è 'application/json'.
     * @param array $headerPersonalizzati Header HTTP personalizzati da includere nella richiesta.
     * @param int $timeoutTotale Il timeout totale per la richiesta in secondi. Di default è 30 secondi.
     * @param int $timeoutConnessione Il timeout per la connessione in secondi. Di default è 10 secondi.
     * @return array Risposta dell'API decodificata in formato array.
     * @throws InvalidArgumentException Se i parametri obbligatori non sono validi.
     * @throws Exception In caso di errore nella chiamata all'endpoint o nella risposta dell'API.
     */
    public function callApiEndpoint(string $pathOrEndpoint, string $metodo = "GET", array $dati = [], string $contentType = 'application/json', array $headerPersonalizzati = [], int $timeoutTotale = 30, int $timeoutConnessione = 10) {
    // Validazione del parametro $pathOrEndpoint
    if (empty($pathOrEndpoint)) {
        throw new InvalidArgumentException("Il parametro 'pathOrEndpoint' non può essere vuoto.");
    }

    // Validazione del parametro $metodo
    $metodiValidi = ['GET', 'POST'];
    if (!in_array(strtoupper($metodo), $metodiValidi)) {
        throw new InvalidArgumentException("Metodo HTTP non supportato: " . $metodo);
    }

    $url = $this->APIbaseURL($pathOrEndpoint);

    if (strtoupper($metodo) === "GET" && !empty($dati)) {
        $url .= '?' . http_build_query($dati);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutTotale); // Timeout totale
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutConnessione); // Timeout di connessione


    if (!$this->CheckSSL){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    // Inizializza gli header con 'Content-Type' e 'X-Api-Key'
    $header = [
        "Content-Type: $contentType",
        "X-Api-Key: " . $this->APIkey
    ];

    // Aggiungi gli header personalizzati agli header di default
    $header = array_merge($header, $headerPersonalizzati);
    // Imposta gli header HTTP
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    // Imposta il metodo HTTP e i dati
    switch (strtoupper($metodo)) {
        case "POST":
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($dati)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dati));
            }
            break;
    }

    // Imposta CURLOPT_RETURNTRANSFER per ottenere il risultato come stringa
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Esegui la chiamata cURL
    $response = curl_exec($ch);
    $ResponseContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    // Controlla se ci sono stati errori nella chiamata cURL
    if ($response === false) {
        $errorCode = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // Controlla se si tratta di un timeout
        if ($errorCode === CURLE_OPERATION_TIMEDOUT) {
            throw new Exception("Timeout della richiesta raggiunto: ".$error);
        }

        // Gestisci altri errori di connessione
        throw new Exception("Errore EndPoint: " . $error);
    }

    // Ottieni il codice di risposta HTTP
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (!($httpCode >= 200 && $httpCode < 300)) {
        curl_close($ch);
        throw new Exception("Errore HTTP: " . $httpCode);
    }
    // Chiudi la sessione cURL
    curl_close($ch);

    // Elaborazione della risposta in base al suo tipo di contenuto
    $managedContentTypes = ['application/json', 'text/xml', 'application/xml'];
    $processAs = in_array($ResponseContentType, $managedContentTypes) ? $ResponseContentType : $contentType;

    switch ($processAs) {
        case 'application/json':
            // Gestione della risposta JSON
            $oggetto = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Errore nella decodifica JSON: " . json_last_error_msg());
            }
            break;

        case 'text/xml':
        case 'application/xml':
            // Gestione della risposta XML
            libxml_use_internal_errors(true);
            $oggetto = simplexml_load_string($response);
            if ($oggetto === false) {
                $error = libxml_get_errors();
                libxml_clear_errors();
                throw new Exception("Errore nel parsing XML: " . implode(", ", $error));
            }
            break;

        default:
            // Gestione di altri tipi di contenuto (come testo semplice o HTML)
            $oggetto = $response;
            break;
    }
    // Restituisci l'oggetto decodificato o la risposta grezza
    return $oggetto;

    }


    /**
     * Converte una stringa in entità HTML.
     *
     * Questa funzione prende una stringa come input e la converte
     * in entità HTML, utilizzando il valore ASCII di ogni carattere
     * della stringa per formare l'entità. Questo è utile per
     * visualizzare i caratteri speciali in HTML.
     *
     * @param string $stringa La stringa da convertire in entità HTML.
     * @return string La stringa convertita in entità HTML.
     */
    public function convertiInEntitaHTML($stringa) {
        $risultato = '';
        $lunghezza = strlen($stringa);
        for ($i = 0; $i < $lunghezza; $i++) {
            $risultato .= '&#' . ord($stringa[$i]) . ';';
        }
        return $risultato;
    }

    /**
     * Crea un link HTML con l'URL codificato e attributi personalizzabili.
     *
     * Questa funzione genera un link HTML che, quando cliccato, attiva una funzione JavaScript
     * 'openEncodedLink' con un prefisso e un URL codificato come parametri.
     * Gli attributi aggiuntivi come class, target, title, rel, id, style, data-* e aria-*
     * possono essere inclusi per personalizzare ulteriormente il link.
     *
     * @param string $url L'URL da includere nel link.
     * @param string $prefisso Il prefisso da utilizzare (es: 'mailto:', 'tel:'). Se non specificato, non viene usato alcun prefisso.
     * @param array $attributiExtra Un array associativo di attributi HTML aggiuntivi e i loro valori. Esempio: ['class' => 'my-class', 'id' => 'my-id'].
     * @return string Il codice HTML del link generato.
     */
    function creaLinkCodificato($url, $prefisso = '', $attributiExtra = []) {
        $urlCodificato = $this->convertiInEntitaHTML($url);
        $attributi = '';

        foreach ($attributiExtra as $chiave => $valore) {
            $attributi .= $chiave . '="' . htmlspecialchars($valore) . '" ';
        }

        return "<a href=\"#\" onClick=\"openEncodedLink('$prefisso', '$urlCodificato')\" $attributi>$urlCodificato</a>";
    }


    /**
     * Determina se è preferibile il testo di colore scuro o chiaro basato sulla luminosità del colore di sfondo.
     *
     * Questa funzione calcola la luminosità di un colore dato in formato HEX e restituisce un valore booleano.
     * Restituisce 'true' se un testo scuro (nero) è preferibile per garantire una buona leggibilità sullo sfondo,
     * altrimenti 'false' per un testo chiaro (bianco). Utilizza una formula di luminosità relativa che
     * tiene conto della diversa sensibilità dell'occhio umano ai colori rosso, verde e blu.
     *
     * @param string $hexColor Il colore di sfondo in formato HEX, come una stringa (es. '#ffcc00').
     * @return bool Restituisce 'true' se il testo scuro è preferibile, altrimenti 'false'.
     */
    function isDarkTextPreferred($hexColor) {
        // Rimuove il carattere # se presente
        $hex = ltrim($hexColor, '#');

        // Converte HEX in RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calcola la luminosità
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        // Restituisce true per il testo scuro se la luminosità è superiore a 0.5, altrimenti false per il testo chiaro
        return $luminance > 0.5;
    }

    /**
     * Scurisce un colore HEX dato di un fattore specificato.
     *
     * Questa funzione converte il colore HEX in formato RGB, applica un fattore di scurimento ai valori RGB,
     * e poi converte i valori RGB scuriti di nuovo in formato HEX. È utile per creare varianti di colore più scure.
     *
     * @param string $hexColor Il colore originale in formato HEX (es. '#ffcc00').
     * @param float $darkenFactor Il fattore di scurimento, dove 1.0 lascia il colore invariato e 0.0 lo rende nero. Default a 0.2.
     * @return string Il colore HEX scurito.
     */
    function darkenColor($hexColor, $darkenFactor = 0.2) {
        // Converti HEX in RGB
        $hex = ltrim($hexColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Applica il fattore di scurimento
        $r = max(0, $r - $r * $darkenFactor);
        $g = max(0, $g - $g * $darkenFactor);
        $b = max(0, $b - $b * $darkenFactor);

        // Converti di nuovo in HEX e restituisci
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Schiarisce un colore HEX dato di un fattore specificato.
     *
     * Questa funzione converte il colore HEX in formato RGB, applica un fattore di schiarimento ai valori RGB,
     * e poi converte i valori RGB schiariti di nuovo in formato HEX. È utile per creare varianti di colore più chiare.
     *
     * @param string $hexColor Il colore originale in formato HEX (es. '#ffcc00').
     * @param float $lightenFactor Il fattore di schiarimento, dove 1.0 lascia il colore invariato e 2.0 lo rende il più chiaro possibile. Default a 1.2.
     * @return string Il colore HEX schiarito.
     */
    function lightenColor($hexColor, $lightenFactor = 1.2) {
        // Converti HEX in RGB
        $hex = ltrim($hexColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Applica il fattore di schiarimento
        $r = min(255, $r * $lightenFactor);
        $g = min(255, $g * $lightenFactor);
        $b = min(255, $b * $lightenFactor);

        // Converti di nuovo in HEX e restituisci
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

}

?>

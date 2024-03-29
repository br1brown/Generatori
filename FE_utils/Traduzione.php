<?php

class Traduzione
{


    /** @var array La traduzione corrente */
    public $corrente = [];

    /** @var string lingua della pagina */
    public string $lang;

    public function __construct(string $l, string $path = __DIR__ . "/lang")
    {
        if (empty($l))
            return;

        $this->lang = $l;

        $files = glob($path . "/{$l}/*.json");
        foreach ($files as $file) {
            $this->corrente = array_merge($this->corrente, json_decode(file_get_contents($file), true));
        }
    }

    public static function listaLingue(string $path = __DIR__ . "/lang")
    {
        $result = [];
        $dirs = array_filter(glob($path . '/*'), 'is_dir');
        foreach ($dirs as $dir) {
            $lingua = strtolower(basename($dir));
            if (!str_starts_with($lingua, '_'))
                $result[] = $lingua;
        }

        return $result;
    }

    /**
     * Tenta di tradurre una stringa (identificatore di traduzione) nella lingua corrente impostata per l'istanza.
     * @param string $sz L'identificatore della stringa da tradurre
     * @return string La stringa tradotta se disponibile; altrimenti, restituisce l'identificatore originale
     */
    function traduci($sz, string ...$valori)
    {
        if (isset($this->corrente[$sz]) && !empty($this->corrente[$sz])) {
            $formatString = $this->corrente[$sz];
        } else {
            $formatString = $sz;
        }

        foreach ($valori as $index => $value) {
            $formatString = str_replace("{" . $index . "}", $value, $formatString);
        }

        return $formatString;
    }
}

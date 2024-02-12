<?php
include __DIR__.'/BLL/auth_and_cors_middleware.php';

function eseguiGET(){
echo Echo_getObj("locali",function ($data) {
    $titoli = $data['titoli'];
    $nomelocale = selezionaCore($data,1,)[0];

    $result = replaceStandard($data,$nomelocale);
    $nomeCompleto = "";
    if (rand(0, 3) > 2)
        $nomeCompleto = $titoli[array_rand($titoli)] . " " . $result;
    else
        $nomeCompleto = $titoli[array_rand($titoli)] . " " . $result . " da " . randomName();
    
    return getResult($nomeCompleto);
});
}
include __DIR__.'/BLL/gestione_metodi.php';
?>
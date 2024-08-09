<?php
include __DIR__.'/BLL/auth_and_cors_middleware.php';
function eseguiGET(){

echo Echo_getObj("incel", function ($data) {
    $frasi = selezionaCore(
                            $data,
                            rand(3,4),
                            ['definisce'],
                            [
                                ranges_eta(true),
                                ['[professioni]']
                            ]
                    );

    $result= randomName('m',rand(0,1) == 0). "\n";
    $result .= implodeRandomSeparator([". ", "; ",".\n"],$frasi);
    $lastSeparatorIndex = -1;

    foreach (['comportamenti','percezione'] as $value) {
        selezionaCasualeSenzaDoppioni($data['aggettivi'][$value], $value, $result);
    }

    $result = replaceStandard($data,$result);
    $result = MaiuscoleDopoPunteggiatura($result.".");

    return getResult($result,"#".FormatMarkdownString($result));
});
}
include __DIR__.'/BLL/gestione_metodi.php';

?>

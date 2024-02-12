<?php
include __DIR__.'/BLL/auth_and_cors_middleware.php';

function eseguiGET(){
echo Echo_getObj("auto", function ($data) {
    $result = implodeRandomSeparator([" eh??!! "," cosa mi dici?!! "," oh! "," eh... fighi! "], selezionaCore(
                $data,
                rand(2,3),
                ["guidi"]
            ))."!";


    $result = replaceStandard($data, $result);
    $result = MaiuscoleDopoPunteggiatura($result);

    return getResult($result, FormatMarkdownString($result));
});
}
include __DIR__.'/BLL/gestione_metodi.php';

?>

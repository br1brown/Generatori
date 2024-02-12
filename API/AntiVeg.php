<?php
include __DIR__.'/BLL/auth_and_cors_middleware.php';
function eseguiGET(){

echo Echo_getObj("antiveg", function ($data) {
    $result = implodeRandomSeparator(["!! ","!1! ","!\n"], selezionaCore(
                $data,
                rand(2,3),
                ['[concetto]',"capiscono","credere"]
                ))."!!";

    $result .= "\n\n([nome] [25-65] anni, su [social])";

    $result = replaceStandard($data, $result);
    $result = MaiuscoleDopoPunteggiatura($result);

    return getResult($result, FormatMarkdownString($result));
});
}
include __DIR__.'/BLL/gestione_metodi.php';

?>

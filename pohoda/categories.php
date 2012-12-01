<?php
$content = file_get_contents("php://input");
$file = "categories.xml";

if (!empty($content)) {
    $file = "categories" . date("_Y-m-d_H-i-s") . ".xml";
    file_put_contents($file, $content);
}


// Send feed
header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rsp:responsePack xmlns:rsp="http://www.stormware.cz/schema/response.xsd" version="2.0" id="00000001" state="ok" application="Prestashop" note="Prestashop import">
</rsp:responsePack>
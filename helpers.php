<?php
function ok($data){ echo json_encode(["status"=>"success","data"=>$data], JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $code=400){ http_response_code($code); echo json_encode(["status"=>"error","message"=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function input($k,$d=null){ return isset($_POST[$k]) ? trim($_POST[$k]) : (isset($_GET[$k]) ? trim($_GET[$k]) : $d); }
function require_fields($arr){ foreach($arr as $k){ if(input($k,"")==="") fail("Missing field: $k"); } }

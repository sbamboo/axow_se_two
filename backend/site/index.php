<?php
header('Content-Type: application/json');

http_response_code(200);
echo json_encode(['status' => 'success', 'msg' => 'This API is in early developement, but visit www.axow.se!']);
?>

<?php
// Simple lightweight health check endpoint that does not require DB connection
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo "OK";

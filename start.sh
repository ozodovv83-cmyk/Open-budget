#!/bin/sh
# Railway PORT o'zgaruvchisini beradi; agar biror sababdan bo'sh bo'lsa,
# 8080 portini standart qilib olamiz (shu bilan "Invalid address: 0.0.0.0:$PORT"
# xatosi $PORT shell orqali almashtirilmagan holatlarda ham oldini oladi).
PORT="${PORT:-8080}"
echo "Starting PHP server on 0.0.0.0:$PORT"
exec php -S 0.0.0.0:"$PORT" index.php

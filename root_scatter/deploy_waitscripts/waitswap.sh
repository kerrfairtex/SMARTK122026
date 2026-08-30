#!/bin/bash
end=$(( $(date +%s) + 1200 ))
while [ $(date +%s) -lt $end ]; do
  body=$(curl -sS --retry 3 --retry-delay 5 https://smartcampk12.onrender.com/ 2>/dev/null)
  size=${#body}
  if printf '%s' "$body" | grep -q "Smart Campus K12"; then
    printf '[%s] LANDING LIVE size=%s\n' "$(date +%H:%M:%S)" "$size"
    printf '%s' "$body" > "$HOME/land.html"
    for f in img-education.jpg img-01.jpeg img-campus.jpg img-09.jpeg img-02.jpeg img-03.jpeg; do
      curl -sS -o /dev/null -w "img $f -> %{http_code} size=%{size_download}\n" "https://smartcampk12.onrender.com/assets/images/$f"
    done
    curl -sS -o /dev/null -w "/login.php -> %{http_code}\n" https://smartcampk12.onrender.com/login.php
    curl -sS -o /dev/null -w "/healthz.php -> %{http_code}\n" https://smartcampk12.onrender.com/healthz.php
    break
  fi
  printf '[%s] not yet size=%s wait 30s\n' "$(date +%H:%M:%S)" "$size"
  sleep 30
done

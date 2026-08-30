#!/bin/bash
end=$(( $(date +%s) + 3600 ))
while [ $(date +%s) -lt $end ]; do
  body=$(curl -sS --retry 4 --retry-delay 8 https://smartcampk12.onrender.com/ 2>/dev/null)
  size=${#body}
  loginhp=$(curl -sS --retry 3 --retry-delay 8 -o /dev/null -w "%{http_code}" https://smartcampk12.onrender.com/login.php 2>/dev/null)
  if printf '%s' "$body" | grep -q "Smart Campus K12"; then
    printf '[%s] LANDING LIVE size=%s login.php=%s\n' "$(date +%H:%M:%S)" "$size" "$loginhp"
    printf '%s' "$body" > "$HOME/land.html"
    for f in img-education.jpg img-01.jpeg img-campus.jpg img-09.jpeg img-02.jpeg img-03.jpeg; do
      curl -sS -o /dev/null -w "img $f -> %{http_code} size=%{size_download}\n" "https://smartcampk12.onrender.com/assets/images/$f"
    done
    echo -n "healthz -> "; curl -sS https://smartcampk12.onrender.com/healthz.php 2>/dev/null; echo
    break
  fi
  printf '[%s] not yet root_size=%s login.php=%s wait 30s\n' "$(date +%H:%M:%S)" "$size" "$loginhp"
  sleep 30
done

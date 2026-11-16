#!/bin/bash
# addr.sh [base url] -- what a handful of addresses answer; with no base it also syncs and runs the tests on Fumiko
BASE=${1:-https://localhost:8443}
if [ -z "$1" ]; then
  cd /mnt/e/GGCMS && GGCMS_REPO=/mnt/e/GGCMS GGCMS_CONFIG_REPO=/mnt/e/GreenGluonCMS_Unhireable bash bin/local_sync.sh > /dev/null 2>&1
  export GGCMS_CONFIG_REPO=/mnt/e/GreenGluonCMS_Unhireable GGCMS_WORK_DIR=/mnt/e/ggcms_work
  php8.4 vendor/bin/phpunit 2>&1 | grep -a "Tests:\|FAIL\|ERROR\|^OK\|^[0-9]*)" | head -8
  php8.4 vendor/bin/phpunit --testsuite lint 2>&1 | grep -a "OK\|FAIL\|ERROR"
  service mysql status >/dev/null 2>&1 || service mysql start >/dev/null 2>&1; service apache2 status >/dev/null 2>&1 || service apache2 start >/dev/null 2>&1
  H="-H Host:earthfluent.com -H Cookie:loggedin=0"; Q=""
else
  H=""; Q="?v=$RANDOM"
fi
t() { code=$(curl -sk $H "$BASE$2$Q" -o /tmp/x.html -w "%{http_code}"); echo "$code  $1: $2 -> $(grep -o 'rel="canonical" href="[^"]*' /tmp/x.html | head -1 | cut -c23- | sed 's#https://earthfluent.com##')"; }
t "wrong course, want 404           " /french/patterns/patterns-still-going-or-finished-part-7/
t "wrong course, want 404           " /german/patterns/patterns-still-going-or-finished-part-7/
t "real lesson, want 200            " /spanish/patterns/patterns-still-going-or-finished-part-7/
t "old address, want the same course" /italian/patterns-still-going-or-finished-part-7/
t "old pre-types address, want 200  " /dutch/nouns-animals-part-1/
t "word at its old address          " /spanish/nouns-animals-part-1/dog/
t "bare code shared by 13 courses   " /nouns-animals-part-1/
t "a language page, want 200        " /korean/
t "nothing at all, want 404         " /korean/no-such-thing/

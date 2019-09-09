#!/bin/sh

IDS="BBK01.WT3210
BBK01.WT3211
BBK01.WT3213
BBK01.WT3215
BBK01.WT3217
BBK01.WT3219
BBK01.WT3221
BBK01.WT3223
BBK01.WT3225
BBK01.WT3227
BBK01.WT3229
BBK01.WT3431
BBK01.WT3433
BBK01.WT3435
BBK01.WT3437
BBK01.WT3439
BBK01.WT3441
BBK01.WT3443
BBK01.WT3445
BBK01.WT3447
BBK01.WT3449
"


URL="https://www.bundesbank.de/cae/servlet/CsvDownload?"

for i in $IDS; do
        URL="${URL}tsId=${i}&"
done

URL="${URL}mode=its&its_csvFormat=de&its_currency=default&its_dateFormat=default&its_from=&its_to="

wget -O buba.csv $URL




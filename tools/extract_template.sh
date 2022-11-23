#!/bin/bash

# update main glpi.pot
xgettext *.php */*.php -o locales/glpi.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po \
    --keyword=_n:1,2 --keyword=__s --keyword=__ --keyword=_e --keyword=_x:1c,2 --keyword=_ex:1c,2 --keyword=_sx:1c,2 --keyword=_nx:1c,2,3 --keyword=_sn:1,2

#Update languages
for file in $(ls -1 locales/*.po)
do 
    msgmerge -U locales/$file locales/glpi.pot
done





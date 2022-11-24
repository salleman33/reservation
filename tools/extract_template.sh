#!/bin/bash


SCRIPT_DIR=$(dirname $0)
WORKING_DIR=$(readlink -f "$SCRIPT_DIR/..")


# Define translate function args
F_ARGS_N="1,2"
F_ARGS__S="1"
F_ARGS__="1"
F_ARGS_X="1c,2"
F_ARGS_SX="1c,2"
F_ARGS_NX="1c,2,3"
F_ARGS_SN="1,2"

# Compute POT filename
if [ -f "$WORKING_DIR/setup.php" ]; then
    # setup.php found: it's a plugin.
    NAME="$(grep -m1 "PLUGIN_.*_VERSION" $WORKING_DIR/setup.php | cut -d _ -f 2)"
    EXCLUDE_REGEX="^.\/\(\..*\|\(libs?\|node_modules\|tests\|vendor\)\/\).*"

    # Only strings with domain specified are extracted (use Xt args of keyword param to set number of args needed)
    F_ARGS_N="$F_ARGS_N,4t"
    F_ARGS__S="$F_ARGS__S,2t"
    F_ARGS__="$F_ARGS__,2t"
    F_ARGS_X="$F_ARGS_X,3t"
    F_ARGS_SX="$F_ARGS_SX,3t"
    F_ARGS_NX="$F_ARGS_NX,5t"
    F_ARGS_SN="$F_ARGS_SN,4t"
else
    # using core most probably
    NAME="GLPI"
    EXCLUDE_REGEX="^.\/\(\..*\|\(config\|files\|lib\|marketplace\|node_modules\|plugins\|public\|tests\|tools\|vendor\)\/\).*"
fi;
POTFILE="$WORKING_DIR/locales/${NAME,,}.pot"


if [ ! -d "$WORKING_DIR/locales" ]; then
    mkdir $WORKING_DIR/locales
fi

# Clean existing POT file
rm -f $POTFILE && touch $POTFILE


# Append locales from PHP
cd $WORKING_DIR
xgettext `find -not -regex $EXCLUDE_REGEX -type f -name "*.php"` \
    -o $POTFILE \
    -L PHP \
    --add-comments=TRANS \
    --from-code=UTF-8 \
    --force-po \
    --join-existing \
    --sort-output \
    --keyword=_n:$F_ARGS_N \
    --keyword=__s:$F_ARGS__S \
    --keyword=__:$F_ARGS__ \
    --keyword=_x:$F_ARGS_X \
    --keyword=_sx:$F_ARGS_SX \
    --keyword=_nx:$F_ARGS_NX \
    --keyword=_sn:$F_ARGS_SN

# Update main language
LANG=C msginit --no-translator -i $POTFILE -l en_GB -o $WORKING_DIR/locales/en_GB.po

#Update others languages
for file in $(ls -1 $WORKING_DIR/locales/*.po |grep -v en_GB)
do 
    echo "update $file"
    msgmerge -U $file $POTFILE
done




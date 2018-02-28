rm -rf .tmp
mkdir .tmp
cp -R * .tmp
(cd .tmp && zip -r archive.zip .)

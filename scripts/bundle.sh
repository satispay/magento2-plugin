find . -name '.DS_Store' -type f -delete
rm -rf magento2.zip
rm -rf .tmp
mkdir .tmp && mkdir .tmp
cp -R * .tmp
cd .tmp && zip -r archive.zip .
cd ..
mv .tmp/archive.zip tmp.zip
rm -rf .tmp

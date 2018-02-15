find . -name '.DS_Store' -type f -delete
rm -rf magento2.zip
rm -rf .bundle
mkdir .bundle && mkdir .bundle
cp -R * .bundle
cd .bundle && zip -r archive.zip .
cd ..
mv .bundle/archive.zip bundle.zip
rm -rf .bundle

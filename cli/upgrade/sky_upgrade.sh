#!/bin/bash

export SITE_ID=cf-lite
export LAB_ID=nankai_sky

cd 2.1.5
find . -name '*.php' -exec {} \;
cd ..

cd 2.2.1
find . -name '*.php' -exec {} \;
cd ..

cd 2.2.2
find . -name '*.php' -exec {} \;
cd ..

cd 2.2.4
find . -name '*.php' -exec {} \;
cd ..

cd 2.3 
find . -name '*.php' -exec {} \;
cd ..



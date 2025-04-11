#!/bin/bash
#do not put back slash
site_url="http://sandes.gov.in"
while IFS= read -r line
do
    # chromium-browser --quiet --headless --disable-gpu --screenshot --window-size=1050,2000 "$site_url"/dash/emailtm/$line/ 
    # mv screenshot.png /home/portal_puser/screenshots/org-"$line".png
    google-chrome --quiet --headless=new --disable-gpu --print-to-pdf "$site_url"/dash/emailtm/$line/ 
    mv output.pdf /home/portal_puser/screenshots/org-"$line".pdf
done < <(curl -sk "$site_url"/list/oos)
google-chrome  --quiet --headless=new --disable-gpu  --print-to-pdf  "$site_url"/dash/emailtmorgwise 
mv output.pdf /home/portal_puser/screenshots/org-all-for-managers.pdf


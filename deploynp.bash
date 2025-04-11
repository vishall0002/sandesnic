yarn encore production
rsync -avz --delete public/build/. portal_puser@10.247.138.140:build/.
rsync -avz --delete public/build/. portal_puser@10.247.138.162:build/.
rsync -avz public/resources/. portal_puser@10.247.138.140:resources/.
rsync -avz public/resources/. portal_puser@10.247.138.162:resources/.
git push bp140 beta
git push bp162 beta
ssh portal_puser@10.247.138.140 "bash deploy-master-bp.bash"
ssh portal_puser@10.247.138.162 "bash deploy-master-bp.bash"

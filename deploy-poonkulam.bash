 yarn encore production
 rsync -avz --delete public/build/. portal_puser@10.162.0.178:versions/current/public/build/.
# rsync -avz --delete public/build/. portal_puser@10.247.138.162:build/.
# rsync -avz public/resources/. portal_puser@10.247.138.140:resources/.
# rsync -avz public/resources/. portal_puser@10.247.138.162:resources/.
git push poonkulam fsstaging
ssh portal_puser@10.162.0.178 "cd versions/current && git pull origin fsstaging && bin/console c:c --env=prod"

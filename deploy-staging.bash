yarn encore production
rsync -avz --delete public/build/. gims136:build/.
rsync -avz public/resources/. gims136:resources/.
git push staging staging
ssh gims136 "bash deploy-staging.bash"

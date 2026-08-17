<!-- REM Open the Laravel project directory -->
cd /d D:\xampp\htdocs\laravel\hr
<!--  -->
REM Check the installed Git version
git --version
<!--  -->
REM Configure your Git identity - run once only
git config --global user.name "Your Name"
git config --global user.email "your-email@example.com"
<!--  -->
REM Initialize a new Git repository
git init
<!--  -->
REM Verify that the Laravel environment file is ignored
git check-ignore .env
<!--  -->
REM Review project files before staging
git status --short
<!--  -->
REM Stage all project files except ignored files
git add .
<!--  -->
REM Review the staged files
git status -sb
git diff --cached --stat
<!--  -->
REM Create the first commit
git commit -m "Initial project setup"
<!--  -->
REM Rename the default branch to main
git branch -M main

<!-- REM Connect the local project to the GitHub repository -->
git remote add origin https://github.com/USERNAME/REPOSITORY.git

<!-- REM Verify the configured remote repository -->
git remote -v

<!-- REM Push the main branch to GitHub -->
git push -u origin main

<!-- example -->
git remote add origin https://github.com/omerkhom-droid/hrs.git
git push -u origin main



<!-- update into GitHub -->
<!-- REM Open the project directory -->
cd /d D:\xampp\htdocs\laravel\hr

<!-- REM Switch to the main branch -->
git switch main

<!-- REM Download the latest changes from GitHub safely -->
git pull --ff-only origin main

<!-- REM Create a separate branch for the new update -->
git switch -c agent/update-name

<!-- REM Review all changed and new files -->
git status --short

<!-- REM Clear Laravel cached files -->
php artisan optimize:clear

<!-- REM Run automated tests -->
php artisan test

<!-- REM Check for whitespace errors -->
git diff --check

<!-- REM Stage the reviewed project changes -->
git add .

<!-- REM Review exactly what will be committed -->
git status -sb
git diff --cached --stat

<!-- REM Create a commit for the update -->
git commit -m "Describe the update"

<!-- REM Push the new branch to GitHub -->
git push -u origin agent/update-name

<!-- example -->
git switch -c agent/roles-management
git add .
git commit -m "Add roles and permissions management"
git push -u origin agent/roles-management
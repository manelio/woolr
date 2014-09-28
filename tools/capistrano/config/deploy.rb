require 'capistrano/ext/multistage'

set :stages, ["staging", "production"]
set :default_stage, "staging"

set :application, "woolr"
set :domain, "woolr.com"

set :repository,  "git@github.com:manelio/woolr.git"
set :scm, :git

set :normalize_asset_timestamps, false


# set :scm, :git # You can set :scm explicitly or Capistrano will make an intelligent guess based on known version control directory names
# Or: `accurev`, `bzr`, `cvs`, `darcs`, `git`, `mercurial`, `perforce`, `subversion` or `none`

# role :web, "your web-server here"                          # Your HTTP server, Apache/etc
# role :app, "your app-server here"                          # This may be the same as your `Web` server
# role :db,  "your primary db-server here", :primary => true # This is where Rails migrations will run
# role :db,  "your slave db-server here"

# if you want to clean up old releases on each deploy uncomment this:
# after "deploy:restart", "deploy:cleanup"

# if you're still using the script/reaper helper you will need
# these http://github.com/rails/irs_process_scripts

# If you are using Passenger mod_rails uncomment this:
# namespace :deploy do
#   task :start do ; end
#   task :stop do ; end
#   task :restart, :roles => :app, :except => { :no_release => true } do
#     run "#{try_sudo} touch #{File.join(current_path,'tmp','restart.txt')}"
#   end
# end

set  :keep_releases,  3
set  :use_sudo, false

set :subpath, "web/"

set :copy_exclude, [".git", ".DS_Store", ".gitignore", ".gitmodules", "/web/cache"]

set :app_symlinks, ["/web/cache", "/web/local"]
set :app_shared_dirs, ["/web/cache", "/web/local"]
set :app_shared_files, []


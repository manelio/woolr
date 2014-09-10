server "memocracia.com", :app, :web, :primary => true

set :deploy_to, "/var/www/#{domain}"
set :deploy_via, :remote_cache

set :user,        "#{application}"
set :branch,      "master"

start-offline: stop
	docker-compose --env-file .offline.env -f docker-compose.yaml up -d --remove-orphans php composer webserver ff-proxy

start: stop
	docker-compose --env-file .online.env -f docker-compose.yaml up -d --remove-orphans

stop:
	docker-compose -f docker-compose.yaml down --remove-orphans

generate:
	docker run --rm -v "${PWD}:/local" openapitools/openapi-generator-cli generate \
	--git-host github.com \
	--git-user-id harness \
	--git-repo-id ff-php-client-api \
    -i /local/api.yaml \
    -g php \
    -o /local/api

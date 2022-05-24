start:
	docker-compose -f docker-compose.yaml up -d

stop:
	docker-compose -f docker-compose.yaml down

generate:
	docker run --rm -v "${PWD}:/local" openapitools/openapi-generator-cli generate \
	--git-host github.com \
	--git-user-id harness \
	--git-repo-id ff-php-client-api \
    -i /local/api.yaml \
    -g php \
    -o /local/api

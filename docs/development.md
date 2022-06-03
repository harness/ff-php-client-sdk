## Setup

For sdk development you will need just docker and docker-compose. You can run dev environment with one of two basic commands

offline proxy
```shell
make start-offline
```

online proxy
```shell
make start-offline
```

when you don't need development environment just type:
```shell
make stop

## OpenAPI generator instructions

for generating php client code using swagger file you will need to clone following repository:
```shell
git clone git@github.com:harness/ff-php-client-api.git api
```

and `api` folder will be created in your project tree with all files.

If there is any change to api.yaml then you need to run:
```shell
make generate
```

Generator will recreate new files in `api` folder.

Finally you just need to push changes, go to api folder:
```shell
cd api

./git_push.sh harness ff-php-client-api "commit message"
```

## Install new dependencies using composer

```shell
docker-compose run --rm composer composer require symfony/package:version
```

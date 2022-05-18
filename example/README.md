# Before you Begin

Harness Feature Flags (FF) is a feature management solution that enables users to change the software’s functionality, without deploying new code. FF uses feature flags to hide code or behaviours without having to ship new versions of the software. A feature flag is like a powerful if statement.

For more information, see https://harness.io/products/feature-flags/

To read more, see https://ngdocs.harness.io/category/vjolt35atg-feature-flags

To sign up, https://app.harness.io/auth/#/signup/

This is a sample Laravel PHP app demonstrating the Harness PHP Server SDK integration with Feature Flags.

This application requires:
- docker
- docker-compose

## To use this application, follow the steps as below ##

1) Create a project in Harness with Feature-flags module enabled
2) Create an environment within your project
3) Create a server-side sdk key in your environment **COPY the value from the Admin Console to your clipboard since this value will only be displayed once**
4) Create a boolean feature-flag in the admin console
5) Clone this repository
```
git clone https://github.com/harness/ff-php-server-sdk.git
```
6) Change to the `example` directory
```
cd example
```
7) Add your FF API Key and FF Flag Name to the `.env` file.
```
FF_API_KEY="<your key>"
FF_FLAG_NAME="<your flag name>"
```
8) Build the application using `docker-compose`
```
docker-compose build app
```
9) Run the application using `docker-compose`
```
docker-compose up -d
```
10) In a browser open the following url
```
http://localhost:8000
```
11) Toggle the Feature Flag and refresh the page to see results.

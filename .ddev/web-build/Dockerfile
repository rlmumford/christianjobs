ARG BASE_IMAGE
FROM $BASE_IMAGE

#temp gpg error fix
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
#temp gpg error fix

RUN apt-get update

# Install the blackfire agent and cron.
RUN apt-get install -y --no-install-recommends --no-install-suggests blackfire-agent cron

# Install SASS compilation dependencies.
RUN npm install --global gulp-cli
RUN wget -q https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb && dpkg -i wkhtmltox_0.12.6-1.buster_amd64.deb; apt-get install -fy

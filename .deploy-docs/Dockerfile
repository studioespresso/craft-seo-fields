FROM debian:9.5-slim

LABEL "com.github.actions.name"="Deploy docs!"
LABEL "com.github.actions.description"="Deploy vuepress docs to Github Pages"
LABEL "com.github.actions.icon"="truck"
LABEL "com.github.actions.color"="green"

LABEL "repository"="https://github.com/janhenckens/craft-seo-fields"
LABEL "homepage"="https://github.com/janhenckens"
LABEL "maintainer"="Jan Henckens<jan@studioespresso.co>"

ADD entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]